<?php
namespace Lightwerk\SurfTasks\Task\Database;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Surf".            *
 *                                                                        *
 *                                                                        */

use Lightwerk\SurfRunner\Factory\NodeFactory;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Node;

/**
 * MySQL Import Task
 *
 * @package Lightwerk\SurfTasks
 */
class ImportTask extends AbstractTask {

	/**
	 * @var array
	 */
	protected $options = array(
//		'truncateTables' => array(
//			 'tableName' => TRUE,
//		),
		'sourceFile' => 'mysqldump.sql.gz',
		'credentialsSource' => 'TYPO3\\CMS',
		'nodeName' => '',
	);

	/**
	 * @param Node $node
	 * @param Application $application
	 * @param Deployment $deployment
	 * @param array $options
	 * @return void
	 * @throws \Lightwerk\SurfRunner\Factory\Exception
	 * @throws \TYPO3\Surf\Exception\InvalidConfigurationException
	 * @throws \TYPO3\Surf\Exception\TaskExecutionException
	 */
	public function execute(Node $node, Application $application, Deployment $deployment, array $options = array()) {
		$node = $this->getNode($node, $options);
		$options = $this->getOptions($options);
		$options = array_merge($options, $this->getCredentials($options['credentialsSource'], $node, $application, $deployment, $options));

		$mysqlArguments = $this->getMysqlArguments($options);
		$deploymentPath = !empty($options['deploymentPath']) ? $options['deploymentPath'] : $application->getDeploymentPath();

		$commands = array(
			'cd ' . escapeshellarg($deploymentPath),
			'gzip -d < ' . escapeshellarg($options['sourceFile']) . ' | mysql ' . $mysqlArguments,
		);

		$this->shell->executeOrSimulate($commands, $node, $deployment, FALSE, FALSE);
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