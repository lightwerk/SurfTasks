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
 * MySQL Dump Task.
 */
class DumpTask extends AbstractTask
{
    /**
     * @var array
     */
    protected $options = [
        'ignoreTables' => [
            '%cache%' => true,
            '%log' => true,
            'be_sessions' => true,
            'cf_%' => true,
            'fe_session_data' => true,
            'fe_sessions' => true,
            'index_%' => true,
            'piwik_%' => true,
            'sys_domain' => true,
            'sys_history' => true,
            'sys_refindex' => true,
            'sys_registry' => true,
            'tx_extensionmanager_domain_model_extension' => true,
            'tx_l10nmgr_index' => true,
            'tx_solr_%' => true,
            'tx_realurl_%' => true,
        ],
        'dumpPath' => '',
        'fullDump' => false,
    ];

    /**
     * @param Node        $node
     * @param Application $application
     * @param Deployment  $deployment
     * @param array       $options
     */
    public function simulate(Node $node, Application $application, Deployment $deployment, array $options = [])
    {
        $this->execute($node, $application, $deployment, $options);
    }

    /**
     * @param Node        $node
     * @param Application $application
     * @param Deployment  $deployment
     * @param array       $options
     *
     * @throws TaskExecutionException
     * @throws InvalidConfigurationException
     */
    public function execute(Node $node, Application $application, Deployment $deployment, array $options = [])
    {
        $options = array_replace_recursive($this->options, $options);
        if (empty($options['sourceNode']) === false) {
            $node = $this->nodeFactory->getNodeByArray($options['sourceNode']);
            $options = array_replace_recursive($options, $options['sourceNodeOptions']);
        }

        $credentials = $this->getCredentials($node, $deployment, $options, $application);
        $mysqlArguments = $this->getMysqlArguments($credentials);
        $tableLikes = $this->getTableLikes($options, $credentials);
        $dumpFile = $this->getDumpFile($options, $credentials);

        $commands = [$this->getDataTablesCommand($mysqlArguments, $tableLikes, $dumpFile)];
        if (empty($tableLikes) === false) {
            $commands[] = $this->getStructureCommand($mysqlArguments, $tableLikes, $dumpFile);
        }

        $this->shell->executeOrSimulate($commands, $node, $deployment, false, false);
    }

    /**
     * @param array $options
     *
     * @return array
     */
    protected function getTableLikes($options, $credentials)
    {
        if ($options['fullDump'] === true || empty($options['ignoreTables']) === true) {
            return [];
        }
        $tablesLike = [];
        foreach ($options['ignoreTables'] as $table => $enabled) {
            if (!$enabled) {
                continue;
            }
            $tablesLike[] = 'Tables_in_'.$credentials['database'].' LIKE '.escapeshellarg($table);
        }

        return $tablesLike;
    }

    /**
     * @param string $mysqlArguments
     * @param array  $tableLikes
     * @param string $targetFile
     *
     * @return string
     */
    protected function getDataTablesCommand($mysqlArguments, $tableLikes, $targetFile)
    {
        if (empty($tableLikes) === false) {
            $dataTables = ' `mysql -N '.$mysqlArguments.' -e "SHOW TABLES WHERE NOT ('.implode(' OR ', $tableLikes).')" | awk \'{printf $1" "}\'`';
        } else {
            $dataTables = '';
        }

        return 'mysqldump --single-transaction '.$mysqlArguments.' '.$dataTables.
        ' | gzip > '.$targetFile;
    }

    /**
     * @param string $mysqlArguments
     * @param array  $tableLikes
     * @param string $targetFile
     *
     * @return string
     */
    protected function getStructureCommand($mysqlArguments, $tableLikes, $targetFile)
    {
        $dataTables = ' `mysql -N '.$mysqlArguments.' -e "SHOW TABLES WHERE ('.implode(' OR ', $tableLikes).')" | awk \'{printf $1" "}\'`';

        return 'mysqldump --no-data --single-transaction '.$mysqlArguments.' --skip-add-drop-table '.$dataTables.
        ' | sed "s/^CREATE TABLE/CREATE TABLE IF NOT EXISTS/g"'.
        ' | gzip >> '.$targetFile;
    }
}
