<?php
namespace Lightwerk\SurfTasks\Task\TYPO3\CMS;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Lightwerk.SurfTasks".   *
 *                                                                        *
 *                                                                        */

use Lightwerk\SurfRunner\Factory\NodeFactory;
use Lightwerk\SurfTasks\Service\RsyncService;
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Surf\Domain\Model\Task;
use TYPO3\Surf\Exception\TaskExecutionException;

/**
 * Rsync Task
 *
 * @package Lightwerk\SurfTasks
 */
class SyncSharedTask extends Task {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Surf\Domain\Service\ShellCommandService
	 */
	protected $shell;

	/**
	 * @Flow\Inject
	 * @var RsyncService
	 */
	protected $rsyncService;

	/**
	 * @Flow\Inject
	 * @var NodeFactory
	 */
	protected $nodeFactory;

	/**
	 * @param Node $node
	 * @param Application $application
	 * @param Deployment $deployment
	 * @param array $options
	 * @return void
	 * @throws \Lightwerk\SurfRunner\Factory\Exception
	 * @throws \TYPO3\Surf\Exception\InvalidConfigurationException
	 * @throws \TYPO3\Surf\Exception\TaskExecutionException
	 */
	public function execute(Node $node, Application $application, Deployment $deployment, array $options = array()) {
		// Get nodes
		if (empty($options['sourceNode']) || !is_array($options['sourceNode'])) {
			throw new TaskExecutionException('No sourceNode given in options.', 1409078366);
		}
		$sourceNode = $this->nodeFactory->getNodeByArray($options['sourceNode']);

		$localhostNode = $deployment->getNode('localhost');

		// Get shared paths
		$sourceSharedPath = $this->getSharedPathFromNode($sourceNode, $deployment, $options);
		if ($sourceSharedPath{0} !== '/') {
			$sourceSharedPath = rtrim($sourceNode->getOption('deploymentPath'), '/') . '/' . $sourceSharedPath;
		}

		$localSharedPath = $deployment->getWorkspacePath($application) . '_shared/';

		$destinationSharedPath = $this->getSharedPathFromNode($node, $deployment, $options);
		if ($destinationSharedPath{0} !== '/') {
			$destinationSharedPath = rtrim($deployment->getApplicationReleasePath($application), '/') . '/' . $destinationSharedPath;
		}

		// Sync folder from source to localhost
		$this->rsyncService->sync(
			// $sourceNode
			$sourceNode,
			// $sourcePath
			$sourceSharedPath,
			// $destinationNode
			$localhostNode,
			// $destinationPath
			$localSharedPath,
			$deployment,
			$options
		);

		// Sync folder from localhost to target
		$this->rsyncService->sync(
			// $sourceNode
			$localhostNode,
			// $sourcePath
			$localSharedPath,
			// $destinationNode
			$node,
			// $destinationPath
			$destinationSharedPath,
			$deployment,
			$options
		);
	}

	/**
	 * @param Node $node
	 * @param Deployment $deployment
	 * @param array $options
	 * @return string
	 * @throws \TYPO3\Surf\Exception\TaskExecutionException
	 */
	protected function getSharedPathFromNode(Node $node, Deployment $deployment, $options) {
		if ($node->hasOption('sharedPath')) {
			$sharedPath = $node->getOption('sharedPath');
		} else {
			if ($node->hasOption('deploymentPath')) {
				$deploymentPath = $node->getOption('deploymentPath');
			} elseif (!empty($options['deploymentPath'])) {
				$deploymentPath = $options['deploymentPath'];
			} else {
				throw new TaskExecutionException('No deploymentPath defined!', 1414849872);
			}

			$commands = array();
			$commands[] = 'cd ' . escapeshellarg($deploymentPath);
			$commands[] = 'readlink ' . escapeshellarg('fileadmin');
			$output = $this->shell->execute($commands, $node, $deployment, TRUE);
			if (!preg_match('/(.+)\/fileadmin\/?$/', trim($output), $matches)) {
				throw new TaskExecutionException('Could not locate fileadmin. Returned value: ' . $output, 1409077056);
			}
			$sharedPath = $matches[1];
		}
		return rtrim($sharedPath, '/') . '/';
	}

	/**
	 * Simulate this task
	 *
	 * @param Node $node
	 * @param Application $application
	 * @param Deployment $deployment
	 * @param array $options
	 * @return void
	 */
	public function simulate(Node $node, Application $application, Deployment $deployment, array $options = array()) {
		$this->execute($node, $application, $deployment, $options);
	}
}