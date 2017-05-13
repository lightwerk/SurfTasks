<?php

namespace Lightwerk\SurfTasks\Task\TYPO3\CMS;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Lightwerk.SurfTasks".   *
 *                                                                        *
 *                                                                        */

use Lightwerk\SurfTasks\Factory\NodeFactory;
use Lightwerk\SurfTasks\Service\RsyncService;
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Surf\Domain\Model\Task;
use TYPO3\Surf\Exception\TaskExecutionException;
use TYPO3\Surf\Exception\InvalidConfigurationException;

/**
 * Rsync Task.
 */
class SyncSharedTask extends ExtbaseCommandTask
{
    /**
     * @Flow\Inject
     *
     * @var \TYPO3\Surf\Domain\Service\ShellCommandService
     */
    protected $shell;

    /**
     * @Flow\Inject
     *
     * @var RsyncService
     */
    protected $rsyncService;

    /**
     * @Flow\Inject
     *
     * @var NodeFactory
     */
    protected $nodeFactory;

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
     * @param Node        $node
     * @param Application $application
     * @param Deployment  $deployment
     * @param array       $options
     *
     * @throws InvalidConfigurationException
     * @throws TaskExecutionException
     */
    public function execute(Node $node, Application $application, Deployment $deployment, array $options = [])
    {
        // Get nodes
        if (empty($options['sourceNode']) || !is_array($options['sourceNode'])) {
            throw new InvalidConfigurationException('No sourceNode given in options.', 1409078366);
        }
        $sourceNode = $this->nodeFactory->getNodeByArray($options['sourceNode']);
        $localNode = $deployment->getNode('localhost');
        $targetNode = $node;

        // Get shared paths
        $sourceSharedPath = $this->getSharedPathFromNode(
            $sourceNode,
            $deployment,
            $options['sourceNodeOptions'],
            $application
        );
        $localSharedPath = $deployment->getWorkspacePath($application).'_shared/';
        $targetSharedPath = $this->getSharedPathFromNode($node, $deployment, $options, $application);

        // maybe we should change this behaviour ...
        if ($targetNode->isLocalhost() === true || $sourceNode->isLocalhost() === true) {
            // sync direct
            $this->sync($sourceNode, $sourceSharedPath, $targetNode, $targetSharedPath, $deployment, $options);
        } else {
            // cached
            $this->sync($sourceNode, $sourceSharedPath, $localNode, $localSharedPath, $deployment, $options);
            $this->sync($localNode, $localSharedPath, $targetNode, $targetSharedPath, $deployment, $options);
        }
    }

    /**
     * @param Node        $node
     * @param Deployment  $deployment
     * @param array       $options
     * @param Application $application
     *
     * @return string
     *
     * @throws InvalidConfigurationException
     * @throws TaskExecutionException
     */
    protected function getSharedPathFromNode(Node $node, Deployment $deployment, $options, Application $application)
    {
        if ($node->hasOption('sharedPath')) {
            $sharedPath = $node->getOption('sharedPath');
        } else {
            if ($node->hasOption('deploymentPath')) {
                $deploymentPath = $node->getOption('deploymentPath');
            } elseif (!empty($options['deploymentPath'])) {
                $deploymentPath = $options['deploymentPath'];
            } else {
                throw new InvalidConfigurationException('No deploymentPath defined!', 1414849872);
            }
            $webDir = $this->getWebDir($deployment, $application);
            if ($webDir !== '') {
                $deploymentPath = rtrim($deploymentPath, '/').'/'.$webDir;
            }

            $commands = [];
            $commands[] = 'cd '.escapeshellarg($deploymentPath);
            $commands[] = 'readlink '.escapeshellarg('fileadmin');
            $output = $this->shell->execute($commands, $node, $deployment, true);
            if (preg_match('/(.+)\/fileadmin\/?$/', trim($output), $matches)) {
                $sharedPath = $matches[1];
            } else {
                $sharedPath = str_replace('htdocs', 'shared', $deploymentPath);
            }
        }
        if ($sharedPath{0} !== '/') {
            $sharedPath = rtrim($deploymentPath, '/').'/'.$sharedPath;
        }

        return rtrim($sharedPath, '/').'/';
    }

    /**
     * @param Node       $sourceNode
     * @param string     $sourcePath
     * @param Node       $targetNode
     * @param string     $targetPath
     * @param Deployment $deployment
     * @param array      $options
     */
    protected function sync(
        Node $sourceNode,
        $sourcePath,
        Node $targetNode,
        $targetPath,
        Deployment $deployment,
        array $options = []
    ) {
        $this->rsyncService->sync(
            $sourceNode,
            $sourcePath,
            $targetNode,
            $targetPath,
            $deployment,
            $options
        );
    }
}
