<?php
namespace Lightwerk\SurfTasks\Task\Database;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Surf".            *
 *                                                                        *
 *                                                                        */

use Lightwerk\SurfRunner\Domain\Model\Application\AbstractApplication;
use Lightwerk\SurfRunner\Factory\NodeFactory;
use Lightwerk\SurfTasks\Service\DatabaseCredentialsService;
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
	 * @param Node $node
	 * @param array $options
	 * @return Node
	 * @throws InvalidConfigurationException
	 * @throws \Lightwerk\SurfRunner\Factory\Exception
	 */
	protected function getNode(Node $node, array $options) {
		if (empty($options['nodeName'])) {
			return $node;
		}

		$nodeName = $options['nodeName'];
		if (empty($options[$nodeName]) || !is_array($options[$nodeName])) {
			throw new InvalidConfigurationException('Node "' . $nodeName . '" not found', 1408441582);
		}

		return $this->nodeFactory->getNodeByArray($options[$nodeName]);
	}

	/**
	 * @param array $taskOptions
	 * @return array
	 */
	protected function getOptions($taskOptions) {
		$options = array_merge($this->options, $taskOptions);

		if (!empty($taskOptions['database']) && is_array($taskOptions['database'])) {
			$options = array_merge($options, $taskOptions['database']);
		}

		$nodeName = $options['nodeName'];
		if (!empty($options[$nodeName]['database']) && is_array($options[$nodeName]['database'])) {
			$options = array_merge($options, $options[$nodeName]['database']);
		}

		return $options;
	}

	/**
	 * @param array $options
	 * @return string
	 */
	protected function getMysqlArguments($options) {
		$arguments = array();
		$argumentKeys = array('username', 'password', 'host', 'socket', 'port');

		foreach ($argumentKeys as $key) {
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

		if (!empty($options['database'])) {
			$arguments[] = $options['database'];
		}
		return implode(' ', $arguments);
	}

	/**
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

		$credentials = json_decode($returnedOutput, TRUE);
		if (empty($credentials)) {
			throw new TaskExecutionException('Could not receive database credentials', 1409252546);
		}
		return $credentials;
	}

	/**
	 * @param $credentialsSource
	 * @param Node $node
	 * @param Application $application
	 * @param Deployment $deployment
	 * @param array $options
	 * @return array|mixed
	 * @throws TaskExecutionException
	 */
	protected function getCredentials($credentialsSource, Node $node, Application $application, Deployment $deployment, array $options) {
		switch ($credentialsSource) {
			case 'TYPO3\\CMS':
				$credentials = $this->getCredentialsFromTypo3Cms($node, $application, $deployment, $options);
				break;
			default:
				$credentials = array();
		}
		return $credentials;
	}
}