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
	protected $databaseArguments = array('username', 'password', 'host', 'socket', 'port', 'database');



	/**
	 * Returns node options
	 *
	 * @param Node $node
	 * @param Application $application
	 * @param Deployment $deployment
	 * @param array $options
	 * @return array
	 */
	protected function getNodeOptions(Node $node, Application $application, Deployment $deployment, array $options) {
		$options = array_merge($this->options, $node->getOptions());
		$options = array_merge($options, $this->getCredentials($node, $application, $deployment, $options));
		return $options;
	}

	/**
	 * Returns MySQL Arguments
	 *
	 * @param array $options
	 * @return string
	 */
	protected function getMysqlArguments($options) {
		$arguments = array();

		foreach ($this->databaseArguments as $key) {
			$key = 'db' . ucfirst($key);
			if (empty($options[$key])) {
				continue;
			}
			$value = escapeshellarg($options[$key]);
			if (strlen($key) === 1) {
				$arguments[$key] = '-' . $key . ' ' . $value;
			} else {
				$arguments[$key] = '--' . $key . '=' . $value;
			}
		}

		return implode(' ', $arguments);
	}

	/**
	 * Get credentials from TYPO3 CMS
	 *
	 * @param Node $node
	 * @param Application $application
	 * @param Deployment $deployment
	 * @param array $options
	 * @return mixed
	 * @throws TaskExecutionException
	 */
	protected function getCredentialsFromTypo3Cms(Node $node, Application $application, Deployment $deployment, array $options = array()) {
		$commands = array();
		$commands[] = 'cd ' . escapeshellarg($deployment->getApplicationReleasePath($application));
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
			$newKey = 'db' . ucfirst($key);
			$credentials[$newKey] = $value;
		}

		return $credentials;
	}

	/**
	 * Returns database credentials
	 *
	 * @param Node $node
	 * @param Application $application
	 * @param Deployment $deployment
	 * @param array $options
	 * @return array|mixed
	 * @throws TaskExecutionException
	 */
	protected function getCredentials(Node $node, Application $application, Deployment $deployment, array $options) {
		switch ($options['credentialsSource']) {
			case 'TYPO3\\CMS':
				$credentials = $this->getCredentialsFromTypo3Cms($node, $application, $deployment, $options);
				break;
			default:
				$credentials = array();
		}
		return $credentials;
	}
}