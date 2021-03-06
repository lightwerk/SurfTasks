<?php

namespace Lightwerk\SurfTasks\Task;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Lightwerk.SurfTasks".   *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Domain\Model\Task;
use TYPO3\Surf\Exception\TaskExecutionException;

/**
 * Writes a deployment log on target server.
 */
class DeploymentLogTask extends Task
{
    /**
     * @Flow\Inject
     *
     * @var \TYPO3\Surf\Domain\Service\ShellCommandService
     */
    protected $shell;

    /**
     * Simulate this task.
     *
     * @param Node        $node
     * @param Application $application
     * @param Deployment  $deployment
     * @param array       $options
     */
    public function simulate(Node $node, Application $application, Deployment $deployment, array $options = [])
    {
        $this->execute($node, $application, $deployment, $options);
    }

    /**
     * Executes this task.
     *
     * @param \TYPO3\Surf\Domain\Model\Node        $node
     * @param \TYPO3\Surf\Domain\Model\Application $application
     * @param \TYPO3\Surf\Domain\Model\Deployment  $deployment
     * @param array                                $options
     *
     * @throws TaskExecutionException
     */
    public function execute(Node $node, Application $application, Deployment $deployment, array $options = [])
    {
        $targetPath = isset($options['deploymentLogTargetPath']) ? $options['deploymentLogTargetPath'] : '.';
        $fileName = !empty($options['deploymentLogFileName']) ? $options['deploymentLogFileName'] : 'deployment.log';
        $optionsToLog = !empty($options['deploymentLogOptions']) ? $options['deploymentLogOptions'] : [
            'tag',
            'branch',
            'sha1',
        ];

        $logContent = [
            date('Y-m-d H:i:s (D)'),
            'Application: '.$application->getName(),
            'Deployment: '.$deployment->getName(),
            'Status: '.$deployment->getStatus(),
        ];

        foreach ($optionsToLog as $key) {
            if (!empty($options[$key])) {
                $logContent[] = $key.' = '.$options[$key];
            }
        }

        $commands = [
            'cd '.escapeshellarg($application->getReleasesPath()),
            'echo '.escapeshellarg(implode(' | ', $logContent)).' >> '.rtrim($targetPath, '/').'/'.$fileName,
        ];
        $this->shell->executeOrSimulate($commands, $node, $deployment);
    }
}
