<?php
namespace Lightwerk\SurfTasks\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Lightwerk.SurfTasks".   *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Exception\TaskExecutionException;

/**
 * Database Credentials Service
 *
 * @Flow\Scope("singleton")
 * @package Lightwerk\SurfTasks
 */
class DatabaseCredentialsService {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Surf\Domain\Service\ShellCommandService
	 */
	protected $shell;

	/**
	 * @param Node $node
	 * @param Application $application
	 * @param Deployment $deployment
	 * @param array $options
	 * @return mixed
	 * @throws TaskExecutionException
	 */
	public function getFromTypo3Cms(Node $node, Application $application, Deployment $deployment, array $options = array()) {
		try {
			$credentials = $this->getFromTypo3CmsByCoreApi($node, $application, $deployment, $options);
		} catch (TaskExecutionException $e) {
			// do nothing
		}

		if (empty($credentials)) {
			try {
				$credentials = $this->getFromTypo3CmsByInlinePhp($node, $application, $deployment, $options);
			} catch (TaskExecutionException $e) {
				// do nothing
			}
		}

		if (empty($credentials)) {
			throw new TaskExecutionException('Could not receive database credentials', 1414959051);
		}
		return $credentials;
	}

	/**
	 * @param Node $node
	 * @param Application $application
	 * @param Deployment $deployment
	 * @param array $options
	 * @return mixed
	 * @throws TaskExecutionException
	 */
	public function getFromTypo3CmsByCoreApi(Node $node, Application $application, Deployment $deployment, array $options = array()) {
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
	 * @param Node $node
	 * @param Application $application
	 * @param Deployment $deployment
	 * @param array $options
	 * @return mixed
	 * @throws TaskExecutionException
	 */
	public function getFromTypo3CmsByInlinePhp(Node $node, Application $application, Deployment $deployment, array $options = array()) {
		$commands = array();
		$commands[] = 'cd ' . escapeshellarg($deployment->getApplicationReleasePath($application));
		if (!empty($options['context'])) {
			$commands[] = 'export TYPO3_CONTEXT=' . escapeshellarg($options['context']);
		}
		$commands[] = 'echo ' . escapeshellarg(
'<?php

define(\'TYPO3_MODE\', \'BE\');
define(\'TYPO3_cliMode\', TRUE);
define(\'TYPO3_mainDir\', \'typo3/\');
define(\'PATH_site\', \'' . rtrim($deployment->getApplicationReleasePath($application), '/') . '/\');
define(\'PATH_typo3conf\', PATH_site . \'typo3conf/\');
define(\'PATH_typo3\', PATH_site . TYPO3_mainDir);

if (file_exists(PATH_typo3conf . \'LocalConfiguration.php\')) {
	$GLOBALS[\'TYPO3_CONF_VARS\'] = include(PATH_typo3conf . \'LocalConfiguration.php\');

	if (file_exists(PATH_typo3conf . \'AdditionalConfiguration.php\')) {
		include(PATH_typo3conf . \'AdditionalConfiguration.php\');
	}
	echo json_encode($GLOBALS[\'TYPO3_CONF_VARS\'][\'DB\']);
	exit(0);
}

if (file_exists(PATH_typo3conf . \'localconf.php\')) {
	include(PATH_typo3conf . \'localconf.php\');
	echo json_encode(
		array(
			\'database\' => $typo_db,
			\'host\' => $typo_db_host,
			\'password\' => $typo_db_password,
			\'username\' => $typo_db_username
		)
	);
	exit(0);
} | php');

		$returnedOutput = $this->shell->execute($commands, $node, $deployment, FALSE, FALSE);

		$credentials = json_decode($returnedOutput, TRUE);
		if (empty($credentials)) {
			throw new TaskExecutionException('Could not receive database credentials', 1414959010);
		}
		return $credentials;
	}
}