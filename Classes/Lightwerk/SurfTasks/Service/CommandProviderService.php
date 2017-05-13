<?php

namespace Lightwerk\SurfTasks\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Lightwerk.SurfTasks".   *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;

/**
 * Mysql Service.
 *
 * @Flow\Scope("singleton")
 */
class CommandProviderService
{
    /**
     * @var array
     */
    private $supportedCommandProvider = ['coreapi', 'typo3-console'];

    /**
     * @var string
     */
    private $detectedCommandProvider = '';

    /**
     * @var string
     */
    private $webDir = null;

    /**
     * @param Deployment $deployment
     * @param Application $application
     * @return string
     */
    public function getDetectedCommandProvider(Deployment $deployment, Application $application): string
    {
        if (!empty($this->detectedCommandProvider)) {
            return $this->detectedCommandProvider;
        }

        return $this->detectCommandProvider($deployment, $application);
    }

    /**
     * @param Deployment $deployment
     * @param Application $application
     * @return string
     * @throws CommandProviderException
     */
    private function detectCommandProvider(Deployment $deployment, Application $application): string
    {
        foreach ($this->supportedCommandProvider as $commandProvider) {
            if ($this->hasCommandProvider($commandProvider, $deployment, $application) === false) {
                continue;
            }
            $this->detectedCommandProvider = $commandProvider;
            return $this->detectedCommandProvider;
        }

        throw new CommandProviderException('No command provider found.', 1494672440);
    }

    /**
     * @param Deployment $deployment
     * @param Application $application
     * @return string
     */
    public function getWebDir(Deployment $deployment, Application $application): string
    {
        if ($this->webDir !== null) {
            return $this->webDir;
        }

        $webDir = '';
        $rootPath = $deployment->getWorkspacePath($application);
        $composerFile = $rootPath . '/composer.json';
        if (file_exists($composerFile) === true) {
            $json = json_decode(file_get_contents($composerFile), true);
            if ($json !== null && empty($json['extra']['typo3/cms']['web-dir']) === false) {
                return rtrim($json['extra']['typo3/cms']['web-dir'], '/') . '/';
            }
        }
        $this->webDir = $webDir;
        return $webDir;
    }

    /**
     * @param string $commandProvider
     * @param Deployment $deployment
     * @param Application $application
     * @return bool
     */
    private function hasCommandProvider(string $commandProvider, Deployment $deployment, Application $application): bool
    {
        switch ($commandProvider) {
            case 'coreapi':
                return $this->isCoreapiInstalled($deployment, $application);
            case 'typo3-console':
                return $this->isTypo3ConsolePresent($deployment, $application);
        }
        return false;
    }

    /**
     * @param Deployment $deployment
     * @param Application $application
     * @return bool
     */
    private function isCoreapiInstalled(Deployment $deployment, Application $application): bool
    {
        $webDir = $this->getWebDir($deployment, $application);
        $rootPath = $deployment->getWorkspacePath($application) . '/' . $webDir;
        return is_dir($rootPath . '/typo3conf/ext/coreapi');
    }

    /**
     * @param Deployment $deployment
     * @param Application $application
     * @return bool
     */
    private function isTypo3ConsolePresent(Deployment $deployment, Application $application): bool
    {
        $vendorPath = $deployment->getWorkspacePath($application) . '/vendor';
        return is_dir($vendorPath . '/helhum/typo3-console') && is_link($vendorPath . '/bin/typo3cms');
    }
}
