<?php
namespace Lightwerk\SurfTasks\Service;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Lightwerk.SurfTaks".  *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Log\LoggerInterface;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Exception\InvalidConfigurationException;

/**
 * @Flow\Scope("singleton")
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
		'recursive' => true,
		'times' => true,
		'perms' => true,
		'links' => true,
		'delete' => true,
		'compress' => true,
		'verbose' => true,
		'quiet' => true,
		'rsh' => 'ssh -p 22'
	);

	/**
	 * @param LoggerInterface $logger
	 * @param boolean $dryRun
	 * @return Deployment
	 */
	public function sync(Node $sourceNode, $sourcePath, Node $destinationNode, $destinationPath, Deployment $deployment, $options) {
		$flagOptions = $this->flags;

		if ($sourceNode->isLocalhost() === false && $destinationNode->isLocalhost()  === false) {
			throw new InvalidConfigurationException('Just one external host is allowed!', 1408805638);
		}

		$externalNode = NULL;
		if ($sourceNode->isLocalhost() === false) {
			$externalNode = $sourceNode;
		} elseif ($destinationNode->isLocalhost() === false) {
			$externalNode = $destinationNode;
		}
		if ($externalNode instanceof Node && $externalNode->hasOption('port')) {
			$flagOptions['rsh'] = 'ssh -p ' . (int) $externalNode->getOption('port');
		}

		if (empty($options['keepCvs']) && (empty($options['context']) || preg_match('/^Production.*/', $options['context']))) {
			$flagOptions['exclude'][] = '.git';
			$flagOptions['exclude'][] = '.svn';
		}

		$command = array_merge(array($this->rsyncCommand), $this->getFlags($options, $flagOptions));
		$command[] = $this->getFullPath($sourceNode, $sourcePath);
		$command[] = $this->getFullPath($destinationNode, $destinationPath);

		$localhost = new Node('localhost');
		$localhost->setHostname('localhost');
		$this->shell->executeOrSimulate(implode(' ', $command), $localhost, $deployment);
	}

	/**
	 * @param Node $node
	 * @param string $path
	 * @return string
	 */
	protected function getFullPath(Node $node, $path) {
		$hostArgument = '';
		if ($node->isLocalhost() === false) {
			if ($node->hasOption('username')) {
				$hostArgument .= $node->getOption('username') . '@';
			}
			$hostArgument .= $node->getHostname() . ':';
		}
		return escapeshellarg($hostArgument . rtrim($path, '/') . '/');
	}

	/**
	 * @param array $options
	 * @return array
	 */
	protected function getFlags($options, $flagOptions) {
		$flags = array();

		if (isset($options['rsyncFlags'])) {
			if (is_string($options['rsyncFlags'])) {
				$flags[] = $options['rsyncFlags'];
				$flagOptions = array();
			} elseif (is_array($options['rsyncFlags'])) {
				$flagOptions = array_merge($flags, $options['rsyncFlags']);
			}
		}

		if (!empty($flagOptions) && is_array($flagOptions)) {
			foreach ($flagOptions as $key => $value) {
				if (is_bool($value)) {
					if ($value) {
						$flags[] = '--' . $key;
					}
				} elseif (is_array($value)) {
					foreach ($value as $subValue) {
						$flags[] = '--' . $key . ' ' . escapeshellarg($subValue);
					}
				} elseif (is_string($value)) {
					$flags[] = '--' . $key . ' ' . escapeshellarg($value);
				}
			}
		}

		return $flags;
	}
}