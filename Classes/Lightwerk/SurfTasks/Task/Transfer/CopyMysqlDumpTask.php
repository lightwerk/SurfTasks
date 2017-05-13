<?php

namespace Lightwerk\SurfTasks\Task\Transfer;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Lightwerk.SurfTasks".   *
 *                                                                        *
 *                                                                        */

use Lightwerk\SurfTasks\Factory\NodeFactory;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Domain\Model\Task;
use TYPO3\Surf\Exception\TaskExecutionException;
use TYPO3\Surf\Exception\InvalidConfigurationException;

/**
 * Copy MySQL Dump Task.
 */
class CopyMysqlDumpTask extends Task
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
     * @var NodeFactory
     */
    protected $nodeFactory;

    /**
     * @var array
     */
    protected $options = [
        'sourcePath' => '.',
        'sourceFile' => 'mysqldump.sql.gz',
        'targetPath' => '.',
        'targetFile' => 'mysqldump.sql.gz',
    ];

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
     * Executes the task.
     *
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
        if (empty($options['sourceNode']) || !is_array($options['sourceNode'])) {
            throw new InvalidConfigurationException('SourceNode is missing', 1409263510);
        }
        $sourceNode = $this->nodeFactory->getNodeByArray($options['sourceNode']);

        $options = array_replace_recursive($this->options, $options);

        $source = $this->getArgument($sourceNode, $application, $options['sourcePath'], $options['sourceFile']);
        $target = $this->getArgument($node, $application, $options['targetPath'], $options['targetFile']);
        $command = 'scp -o BatchMode=\'yes\' '.$source.' '.$target;

        $this->shell->executeOrSimulate($command, $node, $deployment);
    }

    /**
     * @param Node        $node
     * @param Application $application
     * @param string      $path
     * @param string      $file
     *
     * @return string
     */
    protected function getArgument(Node $node, Application $application, $path, $file)
    {
        $argument = '';

        if ($node->hasOption('username')) {
            $username = $node->getOption('username');
            if (!empty($username)) {
                $argument .= $username.'@';
            }
        }

        $argument .= $node->getHostname().':';

        $argument .= rtrim($application->getReleasesPath(), '/').'/';
        $argument = rtrim($argument.$path, '/').'/';
        $argument .= $file;

        return $argument;
    }
}
