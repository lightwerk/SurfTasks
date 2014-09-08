<?php
namespace Lightwerk\SurfTasks\Task\TYPO3\CMS;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Surf".            *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Domain\Model\Task;

/**
 * Updates the database schema
 *
 * @package Lightwerk\SurfTasks
 */
class UpdateDatabaseTask extends Task {

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
	 * @return void
	 */
	public function execute(Node $node, Application $application, Deployment $deployment, array $options = array()) {
		// Actions:
		// * 1 = ACTION_UPDATE_CLEAR_TABLE
		// * 2 = ACTION_UPDATE_ADD
		// * 3 = ACTION_UPDATE_CHANGE
		// * 4 = ACTION_UPDATE_CREATE_TABLE
		//   5 = ACTION_REMOVE_CHANGE
		//   6 = ACTION_REMOVE_DROP
		//   7 = ACTION_REMOVE_CHANGE_TABLE
		//   8 = ACTION_REMOVE_DROP_TABLE
		$actions = !empty($options['updateDatabaseActions']) ? $options['updateDatabaseActions'] : '1,2,3,4';

		$commands = array();
		$commands[] = 'cd ' . escapeshellarg($deployment->getApplicationReleasePath($application));
		if (!empty($options['context'])) {
			$commands[] = 'export TYPO3_CONTEXT=' . escapeshellarg($options['context']);
		}
		$commands[] = 'if [ -d "typo3conf/ext/coreapi" ]; then ' .
			'typo3/cli_dispatch.phpsh extbase databaseapi:databasecompare ' . escapeshellarg($actions) . '; ' .
			'fi';

		$this->shell->executeOrSimulate($commands, $node, $deployment);
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