<?php
namespace Lightwerk\SurfTasks\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Lightwerk.SurfTasks".   *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;

/**
 * SSH Tunnel Service
 *
 * example:
 * "options": {
 *  "sshTunnelHostname": "lw-lp@git.lightwerk.com",
 *  "sshTunnelL": "1443:192.168.4.177:443"
 * }
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
	 * @param Application $application
	 * @param Deployment $deployment
	 * @param array $options
	 * @return void
	 * @throws \TYPO3\Surf\Exception\TaskExecutionException
	 */
	public function openTunnel(Application $application, Deployment $deployment, array $options = array()) {
		if (
			$deployment->hasOption('sshTunnelRunningSocketName') ||
			empty($options['sshTunnelHostname']) ||
			empty($options['sshTunnelL'])
		) {
			return;
		}

		$socketName = escapeshellarg($deployment->getWorkspacePath($application) . '-ssh-tunnel-socket');
		$commands = array(
			'mkdir -p ' . escapeshellarg(FLOW_PATH_DATA . 'Surf/'),
			'ssh -f -N -q -M -S ' . $socketName . ' -L ' . $options['sshTunnelL'] . ' ' . $options['sshTunnelHostname'],
		);

		$this->shell->execute($commands, $deployment->getNode('localhost'), $deployment);
		$deployment->setOption('sshTunnelRunningSocketName', $socketName);
	}

	/**
	 * @param Deployment $deployment
	 * @param array $options
	 * @return void
	 * @throws \TYPO3\Surf\Exception\TaskExecutionException
	 */
	public function closeTunnel(Deployment $deployment, array $options = array()) {
		if (
			!$deployment->hasOption('sshTunnelRunningSocketName') ||
			!$deployment->getOption('sshTunnelRunningSocketName') ||
			empty($options['sshTunnelHostname'])
		) {
			return;
		}

		$socketName = $deployment->hasOption('sshTunnelRunningSocketName');
		$commands = array('ssh -S ' . $socketName . ' -O exit ' . $options['sshTunnelHostname']);

		$this->shell->execute($commands, $deployment->getNode('localhost'), $deployment);
		$deployment->setOption('sshTunnelRunningSocketName', FALSE);
	}
}