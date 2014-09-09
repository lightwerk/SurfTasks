<?php
namespace Lightwerk\SurfTasks\Task\Ssh;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Lightwerk.SurfTasks".   *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Domain\Model\Task;

/**
 * Starts an SSH Tunnel
 *
 * @package Lightwerk\SurfTasks
 */
class OpenTunnelTask extends Task {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Surf\Domain\Service\ShellCommandService
	 */
	protected $shell;

	/**
	 * Executes this task
	 *
	 * @param \TYPO3\Surf\Domain\Model\Node $node
	 * @param \TYPO3\Surf\Domain\Model\Application $application
	 * @param \TYPO3\Surf\Domain\Model\Deployment $deployment
	 * @param array $options
	 * @return void
	 */
	public function execute(Node $node, Application $application, Deployment $deployment, array $options = array()) {
		if ($deployment->hasOption('sshTunnelRunningSocketName') || empty($options['sshTunnelHostname']) || empty($options['sshTunnelL'])) {
			$deployment->getLogger()->log('Nothing to do', LOG_DEBUG);
			return;
		}
		$socketName = escapeshellarg('/tmp/surfcaptain-ssh-tunnel-' . $deployment->getName());
		$this->shell->execute(
			'ssh -f -N -q -M -S ' . $socketName . ' -L ' . $options['sshTunnelL'] . ' ' . $options['sshTunnelHostname'],
			$deployment->getNode('localhost'),
			$deployment
		);
		$deployment->setOption('sshTunnelRunningSocketName', $socketName);
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