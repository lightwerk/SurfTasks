<?php
namespace Lightwerk\SurfTasks\Task\Database;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Surf".            *
 *                                                                        *
 *                                                                        */

use Lightwerk\SurfRunner\Factory\NodeFactory;
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
abstract class AbstractTask extends Task {

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
	protected $options = array();

	/**
	 * @var array
	 */
	protected $databaseArguments = array('user', 'password', 'host', 'socket', 'port', 'database');

	/**
	 * @param Node $node
	 * @param Deployment $deployment
	 * @param array $options
	 * @return array|mixed
	 * @throws TaskExecutionException
	 */
	protected function getCredentials(Node $node, Deployment $deployment, array $options) {
		switch ($options['db']['credentialsSource']) {
			case 'TYPO3\\CMS':
				$credentials = $this->getCredentialsFromTypo3Cms($node, $deployment, $options);
				break;
			default:
				$credentials = $options['db'];
		}
		return $credentials;
	}

	/**
	 * @param array $options 
	 * @param array $credentials 
	 * @return string
	 */
	protected function getDumpFile($options, $credentials) {
		if (empty($options['dumpPath']) === FALSE) {
			return $options['dumpPath'] . '/' . $credentials['database'] . '.sql.gz';
		} else {
			return '/tmp/' . $credentials['database'] . '.sql.gz';
		}
	}

	/**
	 * Returns MySQL Arguments
	 *
	 * @param array $options
	 * @return string
	 */
	protected function getMysqlArguments($credentials, $appendDatabase = TRUE) {
		$arguments = array();
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
		if ($appendDatabase === TRUE) {
			return implode(' ', $arguments) . ' ' . $database;
		} else {
			return implode(' ', $arguments);
		}
	}

	/**
	 * @param Node $node
	 * @param Deployment $deployment
	 * @param array $options
	 * @return array
	 * @throws TaskExecutionException
	 */
	protected function getCredentialsFromTypo3Cms(Node $node, Deployment $deployment, array $options = array()) {
		$commands = array();
		$commands[] = 'cd ' . escapeshellarg($options['deploymentPath']);
		if (!empty($options['context'])) {
			$commands[] = 'export TYPO3_CONTEXT=' . escapeshellarg($options['context']);
		}
		$commands[] = 'typo3/cli_dispatch.phpsh extbase configurationapi:show DB';

		$returnedOutput = $this->shell->execute($commands, $node, $deployment, FALSE, FALSE);
		$returnedOutput = json_decode($returnedOutput, TRUE);
		if (empty($returnedOutput)) {
			throw new TaskExecutionException('Could not receive database credentials', 1409252546);
		}

		$credentials = array();
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
}
