<?php

namespace Lightwerk\SurfTasks\Task\Transfer;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Lightwerk.SurfTasks".   *
 *                                                                        *
 *                                                                        */

use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Surf\Domain\Model\Task;
use TYPO3\Surf\Exception\TaskExecutionException;

/**
 * AssureConnectionTask Task.
 */
class AssureConnectionTask extends Task
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
     * @param Node        $node
     * @param Application $application
     * @param Deployment  $deployment
     * @param array       $options
     *
     * @throws TaskExecutionException
     */
    public function execute(Node $node, Application $application, Deployment $deployment, array $options = [])
    {
        if ($node->isLocalhost()) {
            $deployment->getLogger()->log('node seems not to be a remote node', LOG_DEBUG);
        } else {
            $username = $node->hasOption('username') ? $node->getOption('username') : null;
            if (!empty($username)) {
                $username = $username.'@';
            }

            $hostname = $node->getHostname();

            $sshOptions = ['-A', '-q', '-o BatchMode=yes'];
            if ($node->hasOption('port')) {
                $sshOptions[] = '-p '.escapeshellarg($node->getOption('port'));
            }

            $command = 'ssh '.implode(' ', $sshOptions).' '.escapeshellarg($username.$hostname).' exit;';

            $this->shell->execute($command, $deployment->getNode('localhost'), $deployment);
            $deployment->getLogger()->log('SSH connection successfully established', LOG_DEBUG);
        }
    }
}
