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
use TYPO3\Surf\Domain\Model\Task;

abstract class AbstractTask extends Task
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
     * @param array $options
     *
     * @return string
     */
    protected function getFileName(array $options)
    {
        return !empty($options['lockFile_fileName']) ? $options['lockFile_fileName'] : 'SURFCAPTAIN_DEPLOYMENT_IS_RUNNING';
    }

    /**
     * @param array $options
     *
     * @return string
     */
    protected function getTargetPath(array $options)
    {
        return !empty($options['lockFile_targetPath']) ? $options['lockFile_targetPath'] : '.';
    }
}
