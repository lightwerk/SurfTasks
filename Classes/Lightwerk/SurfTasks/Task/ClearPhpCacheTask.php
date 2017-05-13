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
 * Clears the OpCode Cache of PHP.
 */
class ClearPhpCacheTask extends Task
{
    /**
     * @Flow\Inject
     *
     * @var \TYPO3\Flow\Http\Client\Browser
     */
    protected $browser;

    /**
     * @Flow\Inject
     *
     * @var \TYPO3\Flow\Http\Client\CurlEngine
     */
    protected $browserRequestEngine;

    public function initializeObject()
    {
        $this->browserRequestEngine->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->browserRequestEngine->setOption(CURLOPT_SSL_VERIFYHOST, false);
        $this->browser->setRequestEngine($this->browserRequestEngine);
    }

    /**
     * Executes this task.
     *
     * @param \TYPO3\Surf\Domain\Model\Node        $node
     * @param \TYPO3\Surf\Domain\Model\Application $application
     * @param \TYPO3\Surf\Domain\Model\Deployment  $deployment
     * @param array                                $options
     */
    public function execute(Node $node, Application $application, Deployment $deployment, array $options = [])
    {
        $this->executeOrSimulate($node, $application, $deployment, $options);
    }

    /**
     * @param Node        $node
     * @param Application $application
     * @param Deployment  $deployment
     * @param array       $options
     *
     * @throws \TYPO3\Flow\Http\Client\InfiniteRedirectionException
     */
    protected function executeOrSimulate(
        Node $node,
        Application $application,
        Deployment $deployment,
        array $options = []
    ) {
        if (empty($options['clearPhpCacheUris'])) {
            return;
        }
        $uris = is_array($options['clearPhpCacheUris']) ? $options['clearPhpCacheUris'] : [$options['clearPhpCacheUris']];
        foreach ($uris as $uri) {
            $deployment->getLogger()->log('... (localhost): curl "'.$uri.'"', LOG_DEBUG);
            if ($deployment->isDryRun() === false) {
                $this->browser->request($uri);
            }
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
        $this->executeOrSimulate($node, $application, $deployment, $options);
    }
}
