<?php
namespace Lightwerk\SurfTasks\Task\TYPO3\CMS;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Lightwerk.SurfTasks".   *
 *                                                                        *
 *                                                                        */

use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Surf\Exception\TaskExecutionException;

/**
 * @package Lightwerk\SurfTasks
 */
class AssureCacheDirectoryIsWriteableTask extends ExtbaseCommandTask {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Surf\Domain\Service\ShellCommandService
	 */
	protected $shell;

	/**
	 * Executes this task
	 *
	 * @param Node $node
	 * @param Application $application
	 * @param Deployment $deployment
	 * @param array $options
	 * @throws TaskExecutionException
	 * @return void
	 */
	public function execute(Node $node, Application $application, Deployment $deployment, array $options = array()) {
		$commands = $this->buildCommands($deployment, $application, 'coreapi', 'cacheapi:assurecachedirectoryiswriteable', $options);
		if (count($commands) > 0) {
			$this->shell->executeOrSimulate($commands, $node, $deployment);
		}
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
