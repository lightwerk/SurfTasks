<?php
namespace Lightwerk\SurfTasks\Task\Git;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Lightwerk.SurfTasks".   *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Domain\Model\Task;
use TYPO3\Surf\Exception\InvalidConfigurationException;

/**
 * Removes the deploy branch of surf
 *
 * @package Lightwerk\SurfTasks
 */
class RemoveDeployBranchTask extends Task {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Surf\Domain\Service\ShellCommandService
	 */
	protected $shell;

	/**
	 * Executes this task
	 *
	 * @param Node $node
	 * @param Application $application
	 * @param Deployment $deployment
	 * @param array $options
	 * @return void
	 * @throws InvalidConfigurationException
	 */
	public function execute(Node $node, Application $application, Deployment $deployment, array $options = array()) {
		if (empty($options['branch']) || !empty($options['tag'])) {
			return;
		}

		$quietFlag = (isset($options['verbose']) && $options['verbose']) ? '' : '-q';

		$commands = array();
		$commands[] = 'cd ' . escapeshellarg($deployment->getApplicationReleasePath($application));
		$commands[] = 'if [ -d \'.git\' ] && hash git 2>/dev/null; then ' .
			'git checkout ' . $quietFlag . ' ' . escapeshellarg($options['branch']) . ' && ' .
			'git merge --ff ' . $quietFlag . ' ' . escapeshellarg('origin/' . $options['branch']) . ' && ' .
			'git branch -D deploy ' . $quietFlag . '; ' .
		'fi;';

		$this->shell->executeOrSimulate($commands, $node, $deployment);
	}

	/**
	 * Simulate this task
	 *
	 * @param Node $node
	 * @param Application $application
	 * @param Deployment $deployment
	 * @param array $options
	 * @return void
	 */
	public function simulate(Node $node, Application $application, Deployment $deployment, array $options = array()) {
		$this->execute($node, $application, $deployment, $options);
	}

}