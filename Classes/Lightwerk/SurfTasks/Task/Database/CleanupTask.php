<?php
namespace Lightwerk\SurfTasks\Task\Database;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Lightwerk.SurfTasks".   *
 *                                                                        *
 *                                                                        */

use Lightwerk\SurfTasks\Factory\NodeFactory;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Exception\InvalidConfigurationException;
use TYPO3\Surf\Exception\TaskExecutionException;

/**
 * Database Dump Transfer Task
 *
 * @package Lightwerk\SurfTasks
 */
class CleanupTask extends AbstractTask {

	/**
	 * Executes the task
	 *
	 * @param Node $node
	 * @param Application $application
	 * @param Deployment $deployment
	 * @param array $options
	 * @throws InvalidConfigurationException
	 * @throws TaskExecutionException
	 * @return void
	 */
	public function execute(Node $node, Application $application, Deployment $deployment, array $options = array()) {

		$sourceNode = $this->nodeFactory->getNodeByArray($options['sourceNode']);
		$credentials = $this->getCredentials($sourceNode, $deployment, $options['sourceNodeOptions'], $application);
		$dumpFile = $this->getDumpFile($options['sourceNodeOptions'], $credentials);
		$this->shell->executeOrSimulate('rm ' . $dumpFile, $sourceNode, $deployment);

		$credentials = $this->getCredentials($node, $deployment, $options, $application);
		$dumpFile = $this->getDumpFile($options, $credentials);
		$this->shell->executeOrSimulate('rm ' . $dumpFile, $node, $deployment);
	}

	/**
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
