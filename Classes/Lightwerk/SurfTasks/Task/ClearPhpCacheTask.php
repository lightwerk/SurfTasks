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
 * Clears the OpCode Cache of PHP
 *
 * @package Lightwerk\SurfTasks
 */
class ClearPhpCacheTask extends Task {


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
		$this->executeOrSimulate($node, $application, $deployment, $options);
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
		$this->executeOrSimulate($node, $application, $deployment, $options);
	}

	/**
	 * @param Node $node
	 * @param Application $application
	 * @param Deployment $deployment
	 * @param array $options
	 * @throws \TYPO3\Flow\Http\Client\InfiniteRedirectionException
	 */
	protected function executeOrSimulate(Node $node, Application $application, Deployment $deployment, array $options = array()) {
		if (empty($options['clearPhpCacheUris'])) {
			$deployment->getLogger()->log('Nothing to do', LOG_DEBUG);
			return;
		}
		$uris = is_array($options['clearPhpCacheUris']) ? $options['clearPhpCacheUris'] : array($options['clearPhpCacheUris']);
		foreach ($uris as $uri) {
			$deployment->getLogger()->log('... (localhost): curl "' . $uri . '"', LOG_DEBUG);
			if ($deployment->isDryRun() === FALSE) {
				$this->browser->request($uri);
			}
		}
	}

}