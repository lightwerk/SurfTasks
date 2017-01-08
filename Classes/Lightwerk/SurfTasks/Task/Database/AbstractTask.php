<?php
namespace Lightwerk\SurfTasks\Task\Database;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Surf".            *
 *                                                                        *
 *                                                                        */

use Lightwerk\SurfTasks\Factory\NodeFactory;
use Lightwerk\SurfTasks\Task\TYPO3\CMS\ExtbaseCommandTask;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Domain\Model\Task;
use TYPO3\Surf\Exception\InvalidConfigurationException;
use TYPO3\Surf\Exception\TaskExecutionException;

/**
 * Abstract Database Task
 *
 * @package Lightwerk\SurfTasks
 */
abstract class AbstractTask extends ExtbaseCommandTask
{
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
    protected $options = [];

    /**
     * @var array
     */
    protected $databaseArguments = ['user', 'password', 'host', 'socket', 'port', 'database'];

    /**
     * @param Node $node
     * @param Deployment $deployment
     * @param array $options
     * @param Application $application
     * @return array|mixed
     * @throws TaskExecutionException
     * @throws InvalidConfigurationException
     */
    protected function getCredentials(Node $node, Deployment $deployment, array $options, Application $application)
    {
        if (empty($options['db']) === true) {
            throw new InvalidConfigurationException('db is not configured', 1429628542);
        }
        if (empty($options['db']['credentialsSource']) === false) {
            switch ($options['db']['credentialsSource']) {
                case 'TYPO3\\CMS':
                    $credentials = $this->getCredentialsFromTypo3Cms($node, $deployment, $options, $application);
                    break;
                default:
                    throw new InvalidConfigurationException('unknown credentialsSource', 1429628543);
            }
        } else {
            $credentials = $options['db'];
        }
        return $credentials;
    }

    /**
     * @param Node $node
     * @param Deployment $deployment
     * @param array $options
     * @param Application $application
     * @return array
     * @throws TaskExecutionException
     */
    protected function getCredentialsFromTypo3Cms(
        Node $node,
        Deployment $deployment,
        array $options = [],
        Application $application
    ) {
        $commands = $this->buildCommands($deployment, $application, 'coreapi', 'configurationapi:show DB', $options);
        if (empty($commands) === false) {
            // Overwrite first command
            $commands[0] = 'cd ' . escapeshellarg($options['deploymentPath']);
        } else {
            throw new TaskExecutionException('Could not receive database credentials', 1409252547);
        }

        $returnedOutput = $this->shell->execute($commands, $node, $deployment, false, false);
        $returnedOutput = json_decode($returnedOutput, true);
        if (empty($returnedOutput)) {
            throw new TaskExecutionException('Could not receive database credentials', 1409252546);
        }

        $credentials = [];
        foreach ($returnedOutput as $key => $value) {
            switch ($key) {
                case 'username':
                    $credentials['user'] = $value;
                    break;
                default:
                    $credentials[$key] = $value;
                    break;
            }
        }
        return $credentials;
    }

    /**
     * @param array $options
     * @param array $credentials
     * @return string
     */
    protected function getDumpFile($options, $credentials)
    {
        if (empty($options['dumpPath']) === false) {
            return $options['dumpPath'] . '/' . $credentials['database'] . '.sql.gz';
        } else {
            return '/tmp/' . $credentials['database'] . '.sql.gz';
        }
    }

    /**
     * Returns MySQL Arguments
     *
     * @param array $credentials
     * @param bool $appendDatabase
     * @return string
     */
    protected function getMysqlArguments($credentials, $appendDatabase = true)
    {
        $arguments = [];
        $database = '';
        foreach ($this->databaseArguments as $key) {
            if (empty($credentials[$key])) {
                continue;
            }
            $value = escapeshellarg($credentials[$key]);
            if ($key === 'database') {
                $database = $value;
            } else {
                $arguments[$key] = '--' . $key . '=' . $value;
            }
        }
        if ($appendDatabase === true) {
            return implode(' ', $arguments) . ' ' . $database;
        } else {
            return implode(' ', $arguments);
        }
    }
}
