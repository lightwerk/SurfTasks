<?php
namespace Lightwerk\SurfTasks\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Lightwerk.SurfTasks".   *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Surf\Domain\Model\Deployment;

/**
 * SSH Tunnel Service
 *
 * @Flow\Scope("singleton")
 * @package Lightwerk\SurfTasks
 */
class SshTunnelService {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Surf\Domain\Service\ShellCommandService
	 */
	protected $shell;

	/**
	 * @param Deployment $deployment
	 * @param array $options
	 * @return void
	 * @throws \TYPO3\Surf\Exception\TaskExecutionException
	 */
	public function openTunnel(Deployment $deployment, array $options = array()) {
		if ($deployment->hasOption('sshTunnelRunningSocketName') || empty($options['sshTunnelHostname']) || empty($options['sshTunnelL'])) {
			return;
		}
		$socketName = escapeshellarg('/tmp/surfcaptain-ssh-tunnel-' . preg_replace('/[^a-zA-Z0-9-]/', '', $deployment->getName()));
		$this->shell->execute(
			'ssh -f -N -q -M -S ' . $socketName . ' -L ' . $options['sshTunnelL'] . ' ' . $options['sshTunnelHostname'],
			$deployment->getNode('localhost'),
			$deployment
		);
		$deployment->setOption('sshTunnelRunningSocketName', $socketName);
	}

	/**
	 * @param Deployment $deployment
	 * @param array $options
	 * @return void
	 * @throws \TYPO3\Surf\Exception\TaskExecutionException
	 */
	public function closeTunnel(Deployment $deployment, array $options = array()) {
		if (!$deployment->hasOption('sshTunnelRunningSocketName') || empty($options['sshTunnelHostname'])) {
			return;
		}
		$socketName = $deployment->hasOption('sshTunnelRunningSocketName');
		$this->shell->execute(
			'ssh -S ' . $socketName . ' -O exit ' . $options['sshTunnelHostname'],
			$deployment->getNode('localhost'),
			$deployment
		);
		$deployment->setOption('sshTunnelRunningSocketName', FALSE);
	}
}