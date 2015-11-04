<?php
namespace Lightwerk\SurfTasks\Task\TYPO3\CMS;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Surf".            *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Surf\Domain\Model\Task;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Application;

/**
 * Creates the command for an extbase commando
 *
 * @package Lightwerk\SurfTasks
 */
abstract class ExtbaseCommandTask extends Task {

	/**
	 * @param Deployment $deployment
	 * @param Application $application
	 * @param $extensionName
	 * @param $arguments
	 * @param array $options
	 * @return void
	 */
	protected function buildCommands(Deployment $deployment, Application $application, $extensionName, $arguments, $options = array()) {
		$commands = array();
		$command = $this->buildCommand($deployment, $application, $extensionName, $arguments);
		if ($command !== '') {
			$commands[] = 'cd ' . escapeshellarg($deployment->getApplicationReleasePath($application));
			if (!empty($options['context'])) {
				$commands[] = 'export TYPO3_CONTEXT=' . escapeshellarg($options['context']);
			}
			$commands[] = $command;
		}
		return $commands;
	}

	/**
	 * @param Deployment $deployment
	 * @param Application $application
	 * @param $extensionName
	 * @param $arguments
	 * @return string
	 */
	protected function buildCommand(Deployment $deployment, Application $application, $extensionName, $arguments) {
		$command = '';
		$webDir = $this->getWebDir($deployment, $application);
		$rootPath = $deployment->getWorkspacePath($application) . '/' . $webDir;
		if (is_dir($rootPath . '/typo3conf/ext/' . $extensionName) === TRUE) {
			$command = $webDir . 'typo3/cli_dispatch.phpsh extbase ' . $arguments;
		}
		return $command;
	}

	/**
	 * @param Deployment $deployment
	 * @param Application $application
	 * @return string
	 */
	protected function getWebDir(Deployment $deployment, Application $application) {
		$webDir = '';
		$rootPath = $deployment->getWorkspacePath($application);
		$composerFile = $rootPath . '/composer.json';
		if (file_exists($composerFile) === TRUE) {
			$json = json_decode(file_get_contents($composerFile), TRUE);
			if ($json !== NULL && empty($json['extra']['typo3/cms']['web-dir']) === FALSE) {
				return  $json['extra']['typo3/cms']['web-dir'] . '/';
			}
		}
		return $webDir;
	}

}
