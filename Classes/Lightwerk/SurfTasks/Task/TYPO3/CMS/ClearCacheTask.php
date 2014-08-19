<?php
namespace Lightwerk\SurfTasks\Task\TYPO3\CMS;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Surf".            *
 *                                                                        */

use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;

use TYPO3\Flow\Annotations as Flow;

/**
 * Clears caches in the database and the filesystem
 */
class ClearCacheTask extends \TYPO3\Surf\Domain\Model\Task {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Surf\Domain\Service\ShellCommandService
	 */
	protected $shell;

	/**
	 * Executes this task
	 *
	 * @param \TYPO3\Surf\Domain\Model\Node $node
	 * @param \TYPO3\Surf\Application\TYPO3\Flow $application
	 * @param \TYPO3\Surf\Domain\Model\Deployment $deployment
	 * @param array $options
	 * @return void
	 */
	public function execute(Node $node, Application $application, Deployment $deployment, array $options = array()) {
		$commands = array();
		$commands[] = 'cd ' . escapeshellarg($deployment->getApplicationReleasePath($application));
		$commands[] = 'rm -rf ' . escapeshellarg('typo3temp' . DIRECTORY_SEPARATOR . 'Cache');
		$commands[] = 'rm -rf ' . escapeshellarg('typo3conf' . DIRECTORY_SEPARATOR . 'temp_*');
		if (!empty($options['TYPO3_CONTEXT'])) {
			$commands[] = 'export TYPO3_CONTEXT=' . escapeshellarg($options['TYPO3_CONTEXT']);
		}
		$commands[] = 'typo3 ' . DIRECTORY_SEPARATOR . 'cli_dispatch.phpsh extbase cacheapi:clearallcaches';

		$this->shell->executeOrSimulate($commands, $node, $deployment);
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
}