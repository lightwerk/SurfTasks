<?php
namespace Lightwerk\SurfTasks\Task;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Lightwerk.SurfTasks".   *
 *                                                                        *
 *                                                                        */

use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;

use TYPO3\Flow\Annotations as Flow;

/**
 * Writes a deployment log on target server
 */
class DeploymentLogTask extends \TYPO3\Surf\Domain\Model\Task {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Surf\Domain\Service\ShellCommandService
	 */
	protected $shell;

	/**
	 * Executes this task
	 *
	 * @param \TYPO3\Surf\Domain\Model\Node $node
	 * @param \TYPO3\Surf\Domain\Model\Application $application
	 * @param \TYPO3\Surf\Domain\Model\Deployment $deployment
	 * @param array $options
	 * @return void
	 */
	public function execute(Node $node, Application $application, Deployment $deployment, array $options = array()) {
		$releasePath = $deployment->getApplicationReleasePath($application);

		$targetPath = isset($options['deploymentLogTargetPath']) ? $options['deploymentLogTargetPath'] : '.';
		$fileName = !empty($options['deploymentLogFileName']) ? $options['deploymentLogFileName'] : 'deployment.log';
		$optionsToLog = !empty($options['deploymentLogOptions']) ? $options['deploymentLogOptions'] : array('repositoryUrl', 'tag', 'branch', 'sha1');

		$logContent = array(
			date('Y-m-d H:i:s (D)'),
			'Application: ' . $application->getName(),
			'Deployment: ' . $deployment->getName()
		);

		foreach ($optionsToLog as $key) {
			if (!empty($options[$key])) {
				$logContent[] = $key . ' = ' . $options[$key];
			}
		}

		$commands = array(
			'cd ' . escapeshellarg($releasePath),
			'echo ' . escapeshellarg(implode(' | ', $logContent)) . ' >> ' . rtrim($targetPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $fileName
		);
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