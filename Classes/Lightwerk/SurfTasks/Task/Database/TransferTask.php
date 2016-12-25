<?php
namespace Lightwerk\SurfTasks\Task\Database;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Lightwerk.SurfTasks".   *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Exception\InvalidConfigurationException;
use TYPO3\Surf\Exception\TaskExecutionException;

/**
 * Database Dump Transfer Task
 *
 * @package Lightwerk\SurfTasks
 */
class TransferTask extends AbstractTask
{
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
     * Executes the task
     *
     * @param Node $node
     * @param Application $application
     * @param Deployment $deployment
     * @param array $options
     * @throws InvalidConfigurationException
     * @throws TaskExecutionException
     * @return void
     */
    public function execute(Node $node, Application $application, Deployment $deployment, array $options = [])
    {

        $sourceNode = $this->nodeFactory->getNodeByArray($options['sourceNode']);
        $credentials = $this->getCredentials($sourceNode, $deployment, $options['sourceNodeOptions'], $application);
        $dumpFile = $this->getDumpFile($options['sourceNodeOptions'], $credentials);
        $source = $this->getArgument($sourceNode, $dumpFile);

        $credentials = $this->getCredentials($node, $deployment, $options, $application);
        $dumpFile = $this->getDumpFile($options, $credentials);
        $target = $this->getArgument($node, $dumpFile);

        $command = 'scp -o BatchMode=\'yes\' ' . $source . ' ' . $target;
        if ($sourceNode->isLocalhost() === true) {
            $this->shell->executeOrSimulate($command, $sourceNode, $deployment);
        } else {
            $this->shell->executeOrSimulate($command, $node, $deployment);
        }
    }

    /**
     * @param Node $node
     * @param string $file
     * @return string
     */
    protected function getArgument(Node $node, $file)
    {
        if ($node->isLocalhost() === true) {
            return $file;
        }
        $argument = '';

        if ($node->hasOption('port')) {
            $port = $node->getOption('port');
            if (!empty($port)) {
                $argument .= '-P ' . $port . ' ';
            }
        }

        if ($node->hasOption('username')) {
            $username = $node->getOption('username');
            if (!empty($username)) {
                $argument .= $username . '@';
            }
        }
        $argument .= $node->getHostname() . ':';
        $argument .= $file;
        return $argument;
    }
}
