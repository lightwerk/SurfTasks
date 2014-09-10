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

		$socketPath = $deployment->getWorkspacePath($application) . '-ssh-tunnel-socket';
		$socketName = basename($socketPath);
		$socketDirectory = dirname($socketPath);

		$commands = array(
			'mkdir -p ' . escapeshellarg($socketDirectory),
			'cd ' . escapeshellarg($socketDirectory),
			'ssh -o BatchMode=yes -o ExitOnForwardFailure=yes -f -N -M -S ' . $socketName . ' -L ' . escapeshellarg($options['sshTunnelL']) . ' ' . escapeshellarg($options['sshTunnelHostname']),
		);

		$this->shell->execute($commands, $deployment->getNode('localhost'), $deployment);
		$deployment->setOption('sshTunnelRunningSocketName', $socketName);
	}

	/**
	 * @param Application $application
	 * @param Deployment $deployment
	 * @param array $options
	 * @return void
	 * @throws \TYPO3\Surf\Exception\TaskExecutionException
	 */
	public function closeTunnel(Application $application, Deployment $deployment, array $options = array()) {
		if (
			$deployment->hasOption('sshTunnelRunningSocketName') === FALSE ||
			$deployment->getOption('sshTunnelRunningSocketName') === FALSE ||
			empty($options['sshTunnelHostname']) === TRUE
		) {
			return;
		}

		$socketDirectory = dirname($deployment->getWorkspacePath($application));

		$socketName = $deployment->getOption('sshTunnelRunningSocketName');
		$commands = array(
			'cd ' . escapeshellarg($socketDirectory),
			'ssh -o BatchMode=yes -S ' . $socketName . ' -O exit ' . escapeshellarg($options['sshTunnelHostname'])
		);

		$this->shell->execute($commands, $deployment->getNode('localhost'), $deployment);
		$deployment->setOption('sshTunnelRunningSocketName', FALSE);
	}
}