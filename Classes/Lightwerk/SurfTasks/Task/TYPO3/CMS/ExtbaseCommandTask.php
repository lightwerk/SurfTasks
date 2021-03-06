<?php

namespace Lightwerk\SurfTasks\Task\TYPO3\CMS;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Lightwerk.SurfTasks".   *
 *                                                                        */

use Lightwerk\SurfTasks\Service\CommandProviderException;
use Lightwerk\SurfTasks\Service\CommandProviderService;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Domain\Model\Task;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Exception\TaskExecutionException;

/**
 * Creates the command for an extbase command.
 */
abstract class ExtbaseCommandTask extends Task
{
    /**
     * @Flow\Inject
     * @var \TYPO3\Surf\Domain\Service\ShellCommandService
     */
    protected $shell;

    /**
     * @Flow\Inject
     * @var CommandProviderService
     */
    protected $commandProviderService;

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
        try {
            $commands = $this->buildCommands(
                $deployment,
                $application,
                $options
            );
            $this->shell->executeOrSimulate($commands, $node, $deployment);
        } catch (CommandProviderException $e) {
            $deployment->getLogger()->log('Task was skipped! Reason: ' . $e->getMessage(), LOG_WARNING);
        }
    }

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
     * @param Deployment $deployment
     * @param Application $application
     * @param array $options
     *
     * @return array
     * @throws CommandProviderException
     */
    protected function buildCommands(
        Deployment $deployment,
        Application $application,
        $options = []
    ) {
        $commands = [];
        $command = '';
        $commandProvider = $this->commandProviderService->getDetectedCommandProvider($deployment, $application);
        switch ($commandProvider) {
            case CommandProviderService::COMMAND_PROVIDER_COREAPI:
                $command = $this->buildCoreapiCommand($deployment, $application, $options);
                break;
            case CommandProviderService::COMMAND_PROVIDER_TYPO3_CONSOLE:
                $command = $this->buildTypo3ConsoleCommand($options);
                break;
            default:
                // Do nothing
        }

        if ($command === '') {
            throw new CommandProviderException('The used Provider (' . $commandProvider . ') has no implementation for this task.', 1494672441);
        }

        $commands[] = 'cd ' . escapeshellarg($deployment->getApplicationReleasePath($application));
        if (!empty($options['context'])) {
            $commands[] = 'export TYPO3_CONTEXT=' . escapeshellarg($options['context']);
        }
        $commands[] = $command;

        return $commands;
    }

    /**
     * @param Deployment $deployment
     * @param Application $application
     * @param array $options
     * @return string
     */
    protected function buildCoreapiCommand(Deployment $deployment, Application $application, array $options): string
    {
        $arguments = $this->getCoreapiArguments($options);
        if (empty($arguments)) {
            return '';
        }
        return $this->commandProviderService->getWebDir($deployment, $application)
            . 'typo3/cli_dispatch.phpsh extbase '
            . $arguments;
    }

    /**
     * @param array $options
     * @return string
     */
    private function buildTypo3ConsoleCommand(array $options): string
    {
        $arguments = $this->getTypo3ConsoleArguments($options);
        if (empty($arguments)) {
            return '';
        }
        return 'vendor/bin/typo3cms ' . $arguments;
    }

    /**
     * @param array $options
     * @return string
     */
    abstract protected function getCoreapiArguments(array $options);

    /**
     * @param array $options
    * @return string
    */
    abstract protected function getTypo3ConsoleArguments(array $options);
}
