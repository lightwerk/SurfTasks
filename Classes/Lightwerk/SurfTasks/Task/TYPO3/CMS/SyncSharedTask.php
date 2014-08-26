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
		if (empty($options['sourceNode']) || !is_array($options['sourceNode'])) {
			throw new TaskExecutionException('No sourceNode given in options.', 1409078366);
		}
		$sourceNode = $this->nodeFactory->getNodeByArray($options['sourceNode']);

		$sharedPath = $this->getSharedPathFromNode($sourceNode, $application, $deployment, $options);
		$localSharedPath = $deployment->getWorkspacePath($application) . '_shared/';

		$localhost = new Node('localhost');
		$localhost->setHostname('localhost');

		// Sync folder from source to localhost
		$this->rsyncService->sync(
			// $sourceNode
			$sourceNode,
			// $sourcePath
			$sharedPath,
			// $destinationNode
			$localhost,
			// $destinationPath
			$localSharedPath,
			$deployment,
			$options
		);

		// Sync folder from localhost to target
		$this->rsyncService->sync(
		// $sourceNode
			$localhost,
			// $sourcePath
			$localSharedPath,
			// $destinationNode
			$node,
			// $destinationPath
			$sharedPath,
			$deployment,
			$options
		);
	}

	/**
	 * @param Node $node
	 * @param Application $application
	 * @param Deployment $deployment
	 * @return string
	 * @throws \TYPO3\Surf\Exception\TaskExecutionException
	 */
	protected function getSharedPathFromNode(Node $node, Application $application, Deployment $deployment) {
		$commands = array();
		$commands[] = 'cd ' . escapeshellarg($deployment->getApplicationReleasePath($application));
		$commands[] = 'readlink ' . escapeshellarg('fileadmin');
		$output = $this->shell->execute($commands, $node, $deployment, TRUE);
		if (!preg_match('/(.+)\/fileadmin\/?$/', trim($output), $matches)) {
			throw new TaskExecutionException('Could not locate fileadmin. Returned value: ' . $output, 1409077056);
		}
		$sharedPath = rtrim($matches[1], '/') . '/';
		if ($sharedPath{0} !== '/') {
			$sharedPath = rtrim($deployment->getApplicationReleasePath($application), '/') . '/' . $sharedPath;
		}
		return $sharedPath;
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