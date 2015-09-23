<?php
namespace Lightwerk\SurfTasks\Task\Database;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Surf".            *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Exception\InvalidConfigurationException;
use TYPO3\Surf\Exception\TaskExecutionException;

/**
 * MySQL Dump Task
 *
 * @package Lightwerk\SurfTasks
 */
class DumpTask extends AbstractTask {

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
			'tx_realurl_%' => TRUE
		),
		'dumpPath' => '',
		'fullDump' => FALSE,
	);

	/**
	 * @param Node $node
	 * @param Application $application
	 * @param Deployment $deployment
	 * @param array $options
	 * @throws TaskExecutionException
	 * @throws InvalidConfigurationException
	 */
	public function execute(Node $node, Application $application, Deployment $deployment, array $options = array()) {

		$options = array_replace_recursive($this->options, $options);
		if (empty($options['sourceNode']) === FALSE) {
			$node = $this->nodeFactory->getNodeByArray($options['sourceNode']);
			$options = array_replace_recursive($options, $options['sourceNodeOptions']);
		} 

		$credentials = $this->getCredentials($node, $deployment, $options);
		$mysqlArguments = $this->getMysqlArguments($credentials);
		$tableLikes = $this->getTableLikes($options, $credentials);
		$dumpFile = $this->getDumpFile($options, $credentials);

		$commands = array($this->getDataTablesCommand($mysqlArguments, $tableLikes, $dumpFile));
		if (empty($tableLikes) === FALSE) {
			$commands[] = $this->getStructureCommand($mysqlArguments, $tableLikes, $dumpFile);
		}

		$this->shell->executeOrSimulate($commands, $node, $deployment, FALSE, FALSE);
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

	/**
	 * @param array $options
	 * @return array
	 */
	protected function getTableLikes($options, $credentials) {
		if ($options['fullDump'] === TRUE || empty($options['ignoreTables']) === TRUE) {
			return array();
		}
		$tablesLike = array();
		foreach ($options['ignoreTables'] as $table => $enabled) {
			if (!$enabled) {
				continue;
			}
			$tablesLike[] = 'Tables_in_' . $credentials['database'] . ' LIKE ' . escapeshellarg($table);
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
		if (empty($tableLikes) === FALSE) {
			$dataTables = ' `mysql -N ' . $mysqlArguments . ' -e "SHOW TABLES WHERE NOT (' . implode(' OR ', $tableLikes) . ')" | awk \'{printf $1" "}\'`';
		} else {
			$dataTables = '';
		}

		return 'mysqldump --single-transaction ' . $mysqlArguments . ' ' . $dataTables .
			' | gzip > ' . $targetFile;
	}

	/**
	 * @param string $mysqlArguments
	 * @param array $tableLikes
	 * @param string $targetFile
	 * @return string
	 */
	protected function getStructureCommand($mysqlArguments, $tableLikes,  $targetFile) {
		$dataTables = ' `mysql -N ' . $mysqlArguments . ' -e "SHOW TABLES WHERE (' . implode(' OR ', $tableLikes) . ')" | awk \'{printf $1" "}\'`';

		return 'mysqldump --no-data --single-transaction ' . $mysqlArguments . ' --skip-add-drop-table ' . $dataTables . 
			' | sed "s/^CREATE TABLE/CREATE TABLE IF NOT EXISTS/g"' .
			' | gzip >> ' . $targetFile;
	}
}
