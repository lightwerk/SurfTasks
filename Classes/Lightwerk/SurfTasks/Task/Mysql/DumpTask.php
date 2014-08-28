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
 * MySQL Dump Task
 *
 * @package Lightwerk\SurfTasks
 */
class DumpTask extends Task {

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
		'ignoreTables' => array(
			'%cache%' => TRUE,
			'%log' => TRUE,
			'be_sessions' => TRUE,
			'cf_%' => TRUE,
			'fe_session_data' => TRUE,
			'fe_sessions' => TRUE,
			'index_%' => TRUE,
			'piwik_%' => TRUE,
			'sys_domain' => TRUE,
			'sys_history' => TRUE,
			'sys_refindex' => TRUE,
			'sys_registry' => TRUE,
			'tx_extensionmanager_domain_model_extension' => TRUE,
			'tx_l10nmgr_index' => TRUE,
			'tx_solr_%' => TRUE,
		),
		'targetPath' => '.',
		'targetFile' => 'mysqldump.sql.gz',
		'fullImport' => FALSE,
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
		if (!empty($options['sourceNode']) && is_array($options['sourceNode'])) {
			$node = $this->nodeFactory->getNodeByArray($options['sourceNode']);
		}

		$mysqlOptions = array_merge_recursive(
			$this->options,
			$this->databaseCredentialsService->getFromTypo3Cms($node, $application, $deployment, $options),
			!empty($options['mysql']) ? $options['mysql'] : array()
		);
		$mysqlArguments = $this->mysqlService->getMysqlArguments($mysqlOptions);
		$tableLikes = $this->getTableLikes($mysqlOptions);

		$commands = array(
			'cd ' . escapeshellarg($application->getReleasesPath()),
			'cd ' . escapeshellarg($mysqlOptions['targetPath']),
			'echo "" > ' . $mysqlOptions['targetFile'],
			$this->getStructureCommand($mysqlArguments, $tableLikes, $mysqlOptions['targetFile']),
			$this->getDataTablesCommand($mysqlArguments, $tableLikes, $mysqlOptions['targetFile']),
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

	/**
	 * @param array $options
	 * @return array
	 */
	protected function getTableLikes($options) {
		if (empty($mysqlOptions['fullImport']) && !empty($mysqlOptions['ignoreTables'])) {
			return array();
		}
		$tablesLike = array();
		foreach ($options['ignoreTables'] as $table => $enabled) {
			if (!$enabled) {
				continue;
			}
			$tablesLike[] = 'Tables_in_' . $options['host'] . ' LIKE ' . escapeshellarg($table);
		}
		return $tablesLike;
	}

	/**
	 * @param string $mysqlArguments
	 * @param array $tableLikes
	 * @param string $targetFile
	 * @return string
	 */
	protected function getDataTablesCommand($mysqlArguments, $tableLikes,  $targetFile) {
		if (!empty($tableLikes)) {
			$dataTables = ' `mysql -N ' . $mysqlArguments . ' -e "SHOW TABLES WHERE NOT (' . implode(' OR ', $tableLikes) . ')" | awk \'{printf $1" "}\'`';
		} else {
			$dataTables = '';
		}

		return 'mysqldump --single-transaction ' . $mysqlArguments . $dataTables .
			' | gzip >> ' . $targetFile;
	}

	/**
	 * @param string $mysqlArguments
	 * @param array $tableLikes
	 * @param string $targetFile
	 * @return string
	 */
	protected function getStructureCommand($mysqlArguments, $tableLikes,  $targetFile) {
		$structureTables = '`mysql -N ' . $mysqlArguments . ' -e "SHOW TABLES WHERE (' . implode(' OR ', $tableLikes) . ')" | awk \'{printf $1" "}\'`';

		return 'mysqldump --no-data --single-transaction ' . $mysqlArguments . ' --skip-add-drop-table ' . $structureTables .
			' | sed "s/^CREATE TABLE/CREATE TABLE IF NOT EXISTS/g"' .
			' | gzip >> ' . $targetFile;
	}
}