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
 * Clean task
 *
 * @package Lightwerk\SurfTasks
 */
class CleanTask extends Task {

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
		if (isset($options['useApplicationWorkspace']) && $options['useApplicationWorkspace'] === TRUE) {
			$gitRootPath = $deployment->getWorkspacePath($application);
		} else {
			$gitRootPath = $deployment->getApplicationReleasePath($application);
		}

		$quietFlag = (isset($options['verbose']) && $options['verbose']) ? '' : '-q';

		if (!empty($options['nodeName'])) {
			$node = $deployment->getNode($options['nodeName']);
			if ($node === NULL) {
				throw new InvalidConfigurationException(
					sprintf('Node "%s" not found', $options['nodeName']),
					1413298085
				);
			}
		}

		$commands = array(
			'cd ' . escapeshellarg($gitRootPath),
			'if [ -d \'.git\' ] && hash git 2>/dev/null; then ' .
				'git clean ' . $quietFlag . ' -d -ff && ' .
				'git checkout -- .; ' .
				'fi;'
		);


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