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
 * Tags the deployed commit with the node name
 *
 * @package Lightwerk\SurfTasks
 */
class TagNodeDeploymentTask extends Task {

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

		if (!empty($options['disableDeploymentTag'])) {
			return;
		}

		$tagPrefix = isset($options['deploymentTagPrefix']) ? $options['deploymentTagPrefix'] : 'server-';
		$tagName = preg_replace('/[^a-zA-Z0-9-_\.]*/', '', $tagPrefix . $node->getName());
		$quietFlag = (isset($options['verbose']) && $options['verbose']) ? '' : '-q';

		if (!empty($options['nodeName'])) {
			$node = $deployment->getNode($options['nodeName']);
			if ($node === NULL) {
				throw new InvalidConfigurationException(
					sprintf('Node "%s" not found', $options['nodeName']),
					1408441582
				);
			}
		}

		$commands = array(
			'cd ' . escapeshellarg($gitRootPath),
			'git tag --force -- ' . escapeshellarg($tagName),
			'git push origin --force ' . $quietFlag . ' -- ' . escapeshellarg($tagName),
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