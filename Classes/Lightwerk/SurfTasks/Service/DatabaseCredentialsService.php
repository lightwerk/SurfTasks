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
}