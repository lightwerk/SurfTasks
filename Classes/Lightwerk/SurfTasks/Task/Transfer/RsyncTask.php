<?php
namespace Lightwerk\SurfTasks\Task\Transfer;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Lightwerk.SurfTasks".   *
 *                                                                        *
 *                                                                        */

use Lightwerk\SurfTasks\Service\RsyncService;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Domain\Model\Task;
use TYPO3\Surf\Exception\TaskExecutionException;
use TYPO3\Surf\Exception\InvalidConfigurationException;

/**
 * Rsync Task
 *
 * @package Lightwerk\SurfTasks
 */
class RsyncTask extends Task
{
    /**
     * @Flow\Inject
     * @var \TYPO3\Surf\Domain\Service\ShellCommandService
     */
    protected $shell;

    /**
     * @Flow\Inject
     * @var RsyncService
     */
    protected $rsyncService;

    /**
     * Simulate this task
     *
     * @param Node $node
     * @param Application $application
     * @param Deployment $deployment
     * @param array $options
     * @return void
     */
    public function simulate(Node $node, Application $application, Deployment $deployment, array $options = [])
    {
        $this->execute($node, $application, $deployment, $options);
    }

    /**
     * Executes this task
     *
     * @param \TYPO3\Surf\Domain\Model\Node $node
     * @param \TYPO3\Surf\Domain\Model\Application $application
     * @param \TYPO3\Surf\Domain\Model\Deployment $deployment
     * @param array $options
     * @return void
     * @throws InvalidConfigurationException
     * @throws TaskExecutionException
     */
    public function execute(Node $node, Application $application, Deployment $deployment, array $options = [])
    {
        // Sync files
        $this->rsyncService->sync(
            // $sourceNode
            $deployment->getNode('localhost'),
            // $sourcePath
            $deployment->getWorkspacePath($application),
            // $destinationNode
            $node,
            // $destinationPath
            $deployment->getApplicationReleasePath($application),
            $deployment,
            $options
        );
    }
}
