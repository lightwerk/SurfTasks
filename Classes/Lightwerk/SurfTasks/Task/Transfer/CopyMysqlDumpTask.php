<?php
namespace Lightwerk\SurfTasks\Task\Transfer;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Lightwerk.SurfTasks".   *
 *                                                                        *
 *                                                                        */

use Lightwerk\SurfRunner\Factory\NodeFactory;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Domain\Model\Task;
use TYPO3\Surf\Exception\InvalidConfigurationException;

/**
 * Copy MySQL Dump Task
 *
 * @package Lightwerk\SurfTasks
 */
class CopyMysqlDumpTask extends Task {

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
	 * @var array
	 */
	protected $options = array(
		'sourcePath' => '.',
		'sourceFile' => 'mysqldump.sql.gz',
		'targetPath' => '.',
		'targetFile' => 'mysqldump.sql.gz',
	);

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
		if (empty($options['sourceNode']) || !is_array($options['sourceNode'])) {
			throw new InvalidConfigurationException('SourceNode is missing', 1409263510);
		}
		$sourceNode = $this->nodeFactory->getNodeByArray($options['sourceNode']);

		$scpOptions = array_merge_recursive(
			$this->options,
			!empty($options['scp']) ? $options['scp'] : array()
		);

		$source = $this->getArgument($sourceNode, $application, $scpOptions['sourcePath'], $scpOptions['sourceFile']);
		$target = $this->getArgument($node, $application, $scpOptions['targetPath'], $scpOptions['targetFile']);
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
	 * @param string $path
	 * @param string $file
	 * @return string
	 */
	protected function getArgument(Node $node, Application $application, $path, $file) {
		$argument = '';

		if ($node->hasOption('username')) {
			$username = $node->getOption('username');
			if (!empty($username)) {
				$argument .= $username . '@';
			}
		}

		$argument .= $node->getHostname() . ':';

		$argument .= rtrim($application->getReleasesPath(), '/') . '/';
		$argument = rtrim($argument . $path, '/') . '/';
		$argument .= $file;

		return $argument;
	}
}