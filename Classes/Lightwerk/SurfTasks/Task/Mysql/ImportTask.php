<?php
namespace Lightwerk\SurfTasks\Task\Mysql;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Surf".            *
 *                                                                        *
 *                                                                        */

use Lightwerk\SurfRunner\Factory\NodeFactory;
use Lightwerk\SurfTasks\Service\DatabaseCredentialsService;
use Lightwerk\SurfTasks\Service\MysqlService;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Domain\Model\Task;

/**
 * MySQL Import Task
 *
 * @package Lightwerk\SurfTasks
 */
class ImportTask extends Task {

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
	 * @Flow\Inject
	 * @var DatabaseCredentialsService
	 */
	protected $databaseCredentialsService;

	/**
	 * @Flow\Inject
	 * @var MysqlService
	 */
	protected $mysqlService;

	/**
	 * @var array
	 */
	protected $options = array(
		'truncateTables' => array(
			// 'tableName' => TRUE,
		),
		'sourcePath' => '.',
		'sourceFile' => 'mysqldump.sql.gz',
	);

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
		$mysqlOptions = array_merge_recursive(
			$this->options,
			$this->databaseCredentialsService->getFromTypo3Cms($node, $application, $deployment, $options),
			!empty($options['mysql']) ? $options['mysql'] : array()
		);
		$mysqlArguments = $this->mysqlService->getMysqlArguments($mysqlOptions);

		$commands = array(
			'cd ' . escapeshellarg($application->getReleasesPath()),
			'cd ' . escapeshellarg($mysqlOptions['sourcePath']),
			'gzip -d < ' . escapeshellarg($mysqlOptions['sourceFile']) . ' | mysql ' . $mysqlArguments,
		);

		$this->shell->executeOrSimulate($commands, $node, $deployment, FALSE, FALSE);
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