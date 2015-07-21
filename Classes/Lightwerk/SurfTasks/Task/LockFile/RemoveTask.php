<?php
namespace Lightwerk\SurfTasks\Task\LockFile;

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
 * Removes the temporary file named SURFCAPTAIN_DEPLOYMENT_IS_RUNNING
 * on the target system.
 *
 * @package Lightwerk\SurfTasks
 */
class RemoveTask extends AbstractTask {

	/**
	 * Executes this task
	 *
	 * @param \TYPO3\Surf\Domain\Model\Node $node
	 * @param \TYPO3\Surf\Domain\Model\Application $application
	 * @param \TYPO3\Surf\Domain\Model\Deployment $deployment
	 * @param array $options
	 * @return void
	 * @throws TaskExecutionException
	 */
	public function execute(Node $node, Application $application, Deployment $deployment, array $options = array()) {
		$commands = array(
			'cd ' . escapeshellarg(rtrim($application->getReleasesPath(), '/') . '/' . $this->getTargetPath($options)),
			'rm -f ' . escapeshellarg($this->getFileName($options))
		);
		$this->shell->executeOrSimulate($commands, $node, $deployment);

		$commands[0] = 'cd ' . escapeshellarg(rtrim($deployment->getWorkspacePath($application), '/') . '/' . $this->getTargetPath($options));
		$this->shell->executeOrSimulate($commands, $deployment->getNode('localhost'), $deployment);
	}

}