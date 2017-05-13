<?php

namespace Lightwerk\SurfTasks\Task\TYPO3\CMS;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Lightwerk.SurfTasks".   *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Surf\Domain\Model\Task;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Application;

/**
 * Creates the command for an extbase command.
 */
abstract class ExtbaseCommandTask extends Task
{
    /**
     * @param Deployment  $deployment
     * @param Application $application
     * @param string      $extensionName
     * @param string      $arguments
     * @param array       $options
     *
     * @return array
     */
    protected function buildCommands(
        Deployment $deployment,
        Application $application,
        $extensionName,
        $arguments,
        $options = []
    ) {
        $commands = [];
        $command = $this->buildCommand($deployment, $application, $extensionName, $arguments);
        if ($command !== '') {
            $commands[] = 'cd '.escapeshellarg($deployment->getApplicationReleasePath($application));
            if (!empty($options['context'])) {
                $commands[] = 'export TYPO3_CONTEXT='.escapeshellarg($options['context']);
            }
            $commands[] = $command;
        }

        return $commands;
    }

    /**
     * @param Deployment  $deployment
     * @param Application $application
     * @param string      $extensionName
     * @param string      $arguments
     *
     * @return string
     */
    protected function buildCommand(Deployment $deployment, Application $application, $extensionName, $arguments)
    {
        $command = '';
        $webDir = $this->getWebDir($deployment, $application);
        $rootPath = $deployment->getWorkspacePath($application).'/'.$webDir;
        if (is_dir($rootPath.'/typo3conf/ext/'.$extensionName) === true) {
            $command = $webDir.'typo3/cli_dispatch.phpsh extbase '.$arguments;
        }

        return $command;
    }

    /**
     * @param Deployment  $deployment
     * @param Application $application
     *
     * @return string
     */
    protected function getWebDir(Deployment $deployment, Application $application)
    {
        $webDir = '';
        $rootPath = $deployment->getWorkspacePath($application);
        $composerFile = $rootPath.'/composer.json';
        if (file_exists($composerFile) === true) {
            $json = json_decode(file_get_contents($composerFile), true);
            if ($json !== null && empty($json['extra']['typo3/cms']['web-dir']) === false) {
                return rtrim($json['extra']['typo3/cms']['web-dir'], '/').'/';
            }
        }

        return $webDir;
    }
}
