<?php
namespace Lightwerk\SurfTasks\Task;

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
 * Writes a deployment log on target server
 *
 * @package Lightwerk\SurfTasks
 */
class DeploymentLogTask extends Task {


	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Http\Client\Browser
	 */
	protected $browser;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Http\Client\CurlEngine
	 */
	protected $browserRequestEngine;

	/**
	 * @return void
	 */
	public function initializeObject() {
		$this->browserRequestEngine->setOption(CURLOPT_SSL_VERIFYPEER, FALSE);
		$this->browserRequestEngine->setOption(CURLOPT_SSL_VERIFYHOST, FALSE);
		$this->browser->setRequestEngine($this->browserRequestEngine);
	}

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
		if (empty($options['callUri'])) {
			return;
		}
		$deployment->getLogger()->log('... (localhost): curl "' . $options['callUri'] . '"', LOG_DEBUG);
		$this->browser->request($options['callUri']);
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
		$deployment->getLogger()->log('... (localhost): curl "' . $options['callUri'] . '"', LOG_DEBUG);
	}

}