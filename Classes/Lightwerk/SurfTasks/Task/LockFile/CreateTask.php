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
 * Create a temporary file named SURFCAPTAIN_DEPLOYMENT_IS_RUNNING
 * on the target system.
 */
class CreateTask extends AbstractTask
{
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
        $content = [
            date('Y-m-d H:i:s (D)'),
            'Application: '.$application->getName(),
            'Deployment: '.$deployment->getName(),
        ];

        $this->createFile(
            rtrim($application->getReleasesPath(), '/').'/'.$this->getTargetPath($options),
            $content,
            $node,
            $deployment,
            $options
        );

        $this->createFile(
            rtrim($deployment->getWorkspacePath($application), '/').'/'.$this->getTargetPath($options),
            $content,
            $deployment->getNode('localhost'),
            $deployment,
            $options
        );
    }

    /**
     * @param $directoryPath
     * @param $content
     * @param Node       $node
     * @param Deployment $deployment
     * @param array      $options
     *
     * @throws TaskExecutionException
     */
    protected function createFile($directoryPath, $content, Node $node, Deployment $deployment, array $options)
    {
        $commands = [
            'mkdir -p '.escapeshellarg($directoryPath),
            'cd '.escapeshellarg($directoryPath),
            'echo '.escapeshellarg(implode(' | ', $content)).' > '.escapeshellarg($this->getFileName($options)),
        ];
        $this->shell->executeOrSimulate($commands, $node, $deployment);
    }

    /**
     * Rollback this task.
     *
     * @param \TYPO3\Surf\Domain\Model\Node        $node
     * @param \TYPO3\Surf\Domain\Model\Application $application
     * @param \TYPO3\Surf\Domain\Model\Deployment  $deployment
     * @param array                                $options
     */
    public function rollback(Node $node, Application $application, Deployment $deployment, array $options = [])
    {
        $this->removeFile(
            rtrim($application->getReleasesPath(), '/').'/'.$this->getTargetPath($options),
            $node,
            $deployment,
            $options
        );

        $this->removeFile(
            rtrim($deployment->getWorkspacePath($application), '/').'/'.$this->getTargetPath($options),
            $deployment->getNode('localhost'),
            $deployment,
            $options
        );
    }

    /**
     * @param string     $directoryPath
     * @param Node       $node
     * @param Deployment $deployment
     * @param array      $options
     *
     * @throws TaskExecutionException
     */
    protected function removeFile($directoryPath, Node $node, Deployment $deployment, array $options)
    {
        $commands = [
            'cd '.escapeshellarg($directoryPath),
            'rm -f '.escapeshellarg($this->getFileName($options)),
        ];
        $this->shell->executeOrSimulate($commands, $node, $deployment);
    }
}
