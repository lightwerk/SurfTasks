<?php
namespace Lightwerk\SurfTasks\Task\Database;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Lightwerk.SurfTasks".   *
 *                                                                        *
 *                                                                        */

use Lightwerk\SurfRunner\Factory\NodeFactory;
use Lightwerk\SurfTasks\Task\Database\AbstractTask;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Exception\InvalidConfigurationException;

/**
 * Database Dump Transfer Task
 *
 * @package Lightwerk\SurfTasks
 */
class TransferTask extends AbstractTask {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Surf\Domain\Service\ShellCommandService
	 */
	protected $shell;

	/**
	 * @Flow\Inject
	 * @var NodeFactory
	 */
	protected $nodeFactory;

	/**
	 * Executes the task
	 *
	 * @param Node $node
	 * @param Application $application
	 * @param Deployment $deployment
	 * @param array $options
	 * @throws InvalidConfigurationException
	 * @throws \Lightwerk\SurfRunner\Factory\Exception
	 * @return void
	 */
	public function execute(Node $node, Application $application, Deployment $deployment, array $options = array()) {
		$options = array_merge_recursive($this->options, $options);

		$sourceNode = $this->nodeFactory->getNodeByArray($options['sourceNode']);
		$credentials = $this->getCredentials($sourceNode, $deployment, $options['sourceNodeOptions']);
		$dumpFile = $this->getDumpFile($options['sourceNodeOptions'], $credentials);
		$source = $this->getArgument($sourceNode, $application, $dumpFile);

		$credentials = $this->getCredentials($node, $deployment, $options);
		$dumpFile = $this->getDumpFile($options, $credentials);
		$target = $this->getArgument($node, $application, $dumpFile);

		$command = 'scp -o BatchMode=\'yes\' ' . $source . ' ' . $target;
		$this->shell->executeOrSimulate($command, $node, $deployment);
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

	/**
	 * @param Node $node
	 * @param Application $application
	 * @param string $file
	 * @return string
	 */
	protected function getArgument(Node $node, Application $application, $file) {
		$argument = '';
		if ($node->hasOption('username')) {
			$username = $node->getOption('username');
			if (!empty($username)) {
				$argument .= $username . '@';
			}
		}

		$argument .= $node->getHostname() . ':';
		$argument .= $file;
		return $argument;
	}
}
