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
use TYPO3\Surf\Exception\TaskExecutionException;
use TYPO3\Surf\Exception\InvalidConfigurationException;

/**
 * MySQL Import Task.
 */
class ImportTask extends AbstractTask
{
    /**
     * @var array
     */
    protected $options = [
        'truncateTables' => [
            // 'tableName' => TRUE,
        ],
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
     * @throws InvalidConfigurationException
     * @throws TaskExecutionException
     */
    public function execute(Node $node, Application $application, Deployment $deployment, array $options = [])
    {
        $options = array_replace_recursive($this->options, $options);

        $credentials = $this->getCredentials($node, $deployment, $options, $application);
        $dumpFile = $this->getDumpFile($options, $credentials);
        $mysqlArguments = $this->getMysqlArguments($credentials);
        $mysqlAuthArguments = $this->getMysqlArguments($credentials, false);

        $commands = [];
        $commands[] = 'echo "CREATE DATABASE IF NOT EXISTS '.$credentials['database'].
            ' DEFAULT CHARACTER SET = \'utf8\''.
            ' DEFAULT COLLATE \'utf8_general_ci\'"'.
            ' | mysql '.$mysqlAuthArguments;
        $commands[] = 'gzip -d < '.escapeshellarg($dumpFile).' | mysql '.$mysqlArguments;
        if (!empty($options['truncateTables']) && is_array($options['truncateTables'])) {
            $commands[] = $this->getTruncateCommand($mysqlArguments, $options);
        }

        $this->shell->executeOrSimulate($commands, $node, $deployment, false, false);
    }

    /**
     * @param string $mysqlArguments
     * @param array  $options
     *
     * @return string
     */
    protected function getTruncateCommand($mysqlArguments, array $options)
    {
        $truncates = [];
        foreach ($options['truncateTables'] as $table => $enabled) {
            if ($enabled) {
                $truncates = 'TRUNCATE '.escapeshellarg($table);
            }
        }

        return 'mysql -N '.$mysqlArguments.' -e "'.implode('; ', $truncates).'"';
    }
}
