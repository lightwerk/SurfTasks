<?php
namespace Lightwerk\SurfTasks\Task\Assets;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Surf".            *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Domain\Model\Task;
use TYPO3\Surf\Exception\TaskExecutionException;

/**
 * @author Achim Fritz <af@lightwerk.com>
 * @package Lightwerk\SurfTasks
 */
class GruntTask extends Task
{

    /**
     * @Flow\Inject
     * @var \TYPO3\Surf\Domain\Service\ShellCommandService
     */
    protected $shell;

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
     * @param Node $node
     * @param Application $application
     * @param Deployment $deployment
     * @param array $options
     * @return void
     * @throws TaskExecutionException
     */
    public function execute(Node $node, Application $application, Deployment $deployment, array $options = [])
    {

        if (isset($options['useApplicationWorkspace']) && $options['useApplicationWorkspace'] === true) {
            $rootPath = $deployment->getWorkspacePath($application);
        } else {
            $rootPath = $deployment->getApplicationReleasePath($application);
        }
        if (isset($options['relativeRootPath'])) {
            $rootPath .= $options['relativeRootPath'];
        }

        if (!empty($options['nodeName'])) {
            $node = $deployment->getNode($options['nodeName']);
            if ($node === null) {
                throw new InvalidConfigurationException(
                    sprintf('Node "%s" not found', $options['nodeName']),
                    1414781227
                );
            }
        }
        $commands = [];
        $commands[] = 'cd ' . escapeshellarg($rootPath);
        $commands[] = 'if [ "`which grunt`" != "" ] && [ -f "Gruntfile.js" ]; then ' .
            'grunt build; ' .
            'fi';

        $this->shell->executeOrSimulate($commands, $node, $deployment);
    }
}
