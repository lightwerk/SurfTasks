<?php
namespace Lightwerk\SurfTasks\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Lightwerk.SurfTasks".   *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Exception\InvalidConfigurationException;

/**
 * Rsync Service
 *
 * @Flow\Scope("singleton")
 * @package Lightwerk\SurfTasks
 */
class RsyncService {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Surf\Domain\Service\ShellCommandService
	 */
	protected $shell;

	/**
	 * @var string
	 */
	protected $rsyncCommand = 'rsync';

	/**
	 * @var array
	 */
	protected $flags = array(
		'recursive' => TRUE,
		'times' => TRUE,
		'perms' => TRUE,
		'links' => TRUE,
		'delete' => TRUE,
		'compress' => TRUE,
		'verbose' => TRUE,
		'quiet' => TRUE,
		'rsh' => 'ssh -p 22',
		'omit-dir-times' => TRUE,
		'o' => array(
			'BatchMode' => 'yes',
		),
	);

	/**
	 * @param Node $sourceNode
	 * @param string $sourcePath
	 * @param Node $destinationNode
	 * @param string $destinationPath
	 * @param Deployment $deployment
	 * @param array $options
	 * @return void
	 * @throws \TYPO3\Surf\Exception\InvalidConfigurationException
	 */
	public function sync(Node $sourceNode, $sourcePath, Node $destinationNode, $destinationPath, Deployment $deployment, $options) {
		$flagOptions = $this->flags;

		if ($sourceNode->isLocalhost() === FALSE && $destinationNode->isLocalhost()  === FALSE) {
			throw new InvalidConfigurationException('Just one external host is allowed!', 1408805638);
		}

		$externalNode = $this->getFirstExternalNode($sourceNode, $destinationNode);
		if ($externalNode instanceof Node && $externalNode->hasOption('port')) {
			$flagOptions['rsh'] = 'ssh -p ' . (int) $externalNode->getOption('port');
		}

		if (!isset($options['keepVcs']) || empty($options['keepVcs'])) {
			if (empty($options['context']) || !preg_match('/^Development.*/', $options['context'])) {
				$flagOptions['exclude'][] = '.git';
				$flagOptions['exclude'][] = '.svn';
			}
		}

		$command = array_merge(array($this->rsyncCommand), $this->getFlags($options, $flagOptions));
		$command[] = $this->getFullPath($sourceNode, $sourcePath);
		$command[] = $this->getFullPath($destinationNode, $destinationPath);

		$localhost = $deployment->getNode('localhost');
		$this->shell->executeOrSimulate(implode(' ', $command), $localhost, $deployment);
	}

	/**
	 * @param Node $node,... Nodes
	 * @return null|Node
	 */
	protected function getFirstExternalNode($node) {
		$nodes = func_get_args();
		foreach ($nodes as $node) {
			/** @var Node $node */
			if ($node->isLocalhost() === FALSE) {
				return $node;
			}
		}
		return NULL;
	}

	/**
	 * @param Node $node
	 * @param string $path
	 * @return string
	 */
	protected function getFullPath(Node $node, $path) {
		$hostArgument = '';
		if ($node->isLocalhost() === FALSE) {
			if ($node->hasOption('username')) {
				$hostArgument .= $node->getOption('username') . '@';
			}
			$hostArgument .= $node->getHostname() . ':';
		}
		return escapeshellarg($hostArgument . rtrim($path, '/') . '/');
	}

	/**
	 * @param array $options
	 * @param array $flagOptions
	 * @return array
	 */
	protected function getFlags($options, $flagOptions) {
		$flags = array();

		if (isset($options['rsyncFlags']) && is_array($options['rsyncFlags'])) {
			$flagOptions = array_merge($flags, $options['rsyncFlags']);
		}

		foreach ($flagOptions as $key => $value) {
			$prefix = strlen($key) === 1 ? '-' : '--';
			if (is_bool($value)) {
				if ($value) {
					// of example "--quiet"
					$flags[] = $prefix . $key;
				}
			} elseif (is_array($value)) {
				foreach ($value as $subKey => $subValue) {
					if (is_int($subKey)) {
						// of example "--exclude 'dir'"
						$flags[] = $prefix . $key . ' ' . escapeshellarg($subValue);
					} else {
						// of example "-o BatchMode='yes'"
						$flags[] = $prefix . $key . ' ' . $subKey . '=' . $subValue;
					}
				}
			} elseif (is_string($value)) {
				// of example "--rsh 'ssh -p 22'"
				$flags[] = $prefix . $key . ' ' . escapeshellarg($value);
			}
		}

		return $flags;
	}
}