<?php
namespace Lightwerk\SurfTasks\Task\TYPO3\CMS;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Lightwerk.SurfTasks".   *
 *                                                                        *
 *                                                                        */

use Lightwerk\SurfTasks\Factory\NodeFactory;
use Lightwerk\SurfTasks\Service\RsyncService;
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Surf\Domain\Model\Task;
use TYPO3\Surf\Exception\TaskExecutionException;
use TYPO3\Surf\Exception\InvalidConfigurationException;

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
	 * @throws InvalidConfigurationException
	 * @throws TaskExecutionException
	 */
	public function execute(Node $node, Application $application, Deployment $deployment, array $options = array()) {
		// Get nodes
		if (empty($options['sourceNode']) || !is_array($options['sourceNode'])) {
			throw new InvalidConfigurationException('No sourceNode given in options.', 1409078366);
		}
		$sourceNode = $this->nodeFactory->getNodeByArray($options['sourceNode']);
		$localNode = $deployment->getNode('localhost');
		$targetNode = $node;

		// Get shared paths
		$sourceSharedPath = $this->getSharedPathFromNode($sourceNode, $deployment, $options['sourceNodeOptions']);
		$localSharedPath = $deployment->getWorkspacePath($application) . '_shared/';
		$targetSharedPath = $this->getSharedPathFromNode($node, $deployment, $options);

		// maybe we should change this behaviour ...
		if ($targetNode->isLocalhost() === TRUE) {
			// sync direct
			$this->sync($sourceNode, $sourceSharedPath, $targetNode, $targetSharedPath, $deployment, $options);
		} else {
			// cached
			$this->sync($sourceNode, $sourceSharedPath, $localNode, $localSharedPath, $deployment, $options);
			$this->sync($localNode, $localSharedPath, $targetNode, $targetSharedPath, $deployment, $options);
		}
	}

	/**
	 * @param Node $sourceNode 
	 * @param string $sourcePath 
	 * @param Node $targetNode 
	 * @param string $targetPath 
	 * @param Deployment $deployment 
	 * @param array $options 
	 * @return void
	 */
	protected function sync(Node $sourceNode, $sourcePath, Node $targetNode, $targetPath, Deployment $deployment, array $options = array()) {
		$this->rsyncService->sync(
			$sourceNode,
			$sourcePath,
			$targetNode,
			$targetPath,
			$deployment,
			$options
		);
	}

	/**
	 * @param Node $node
	 * @param Deployment $deployment
	 * @param array $options
	 * @return string
	 * @throws InvalidConfigurationException
	 * @throws TaskExecutionException
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
				throw new InvalidConfigurationException('No deploymentPath defined!', 1414849872);
			}

			$commands = array();
			$commands[] = 'cd ' . escapeshellarg($deploymentPath);
			$commands[] = 'readlink ' . escapeshellarg('fileadmin');
			$output = $this->shell->execute($commands, $node, $deployment, TRUE);
			if (preg_match('/(.+)\/fileadmin\/?$/', trim($output), $matches)) {
				$sharedPath = $matches[1];
			} else {
				$sharedPath = str_replace('htdocs', 'shared', $deploymentPath);
			}
		}
		if ($sharedPath{0} !== '/') {
			$sharedPath = rtrim($deploymentPath, '/') . '/' . $sharedPath;
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
