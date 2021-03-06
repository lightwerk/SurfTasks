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
use TYPO3\Surf\Exception\TaskExecutionException;

/**
 * Rsync Service.
 *
 * @Flow\Scope("singleton")
 */
class RsyncService
{
    /**
     * @Flow\Inject
     *
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
    protected $flags = [
        'recursive' => true,
        'times' => true,
        'perms' => true,
        'links' => true,
        'delete' => true,
        'delete-excluded' => false,
        'compress' => true,
        'verbose' => true,
        'quiet' => true,
        'rsh' => 'ssh -p 22 -o BatchMode=yes',
        'omit-dir-times' => true,
        'include' => [],
        'exclude' => [],
    ];

    /**
     * @param Node       $sourceNode
     * @param string     $sourcePath
     * @param Node       $destinationNode
     * @param string     $destinationPath
     * @param Deployment $deployment
     * @param array      $options
     *
     * @throws InvalidConfigurationException
     * @throws TaskExecutionException
     */
    public function sync(
        Node $sourceNode,
        $sourcePath,
        Node $destinationNode,
        $destinationPath,
        Deployment $deployment,
        $options
    ) {

        // first override $this->flags with $options['rsyncFlags']
        if (empty($options['rsyncFlags']) === false) {
            $flagOptions = array_replace_recursive($this->flags, $options['rsyncFlags']);
        } else {
            $flagOptions = $this->flags;
        }

        if ($sourceNode->isLocalhost() === false && $destinationNode->isLocalhost() === false) {
            throw new InvalidConfigurationException('Just one external host is allowed!', 1408805638);
        }

        // override $flagOptions
        $externalNode = $this->getFirstExternalNode($sourceNode, $destinationNode);
        if ($externalNode instanceof Node && $externalNode->hasOption('port')) {
            $flagOptions['rsh'] = 'ssh -p '.(int) $externalNode->getOption('port').' -o BatchMode=yes';
        }

        if (!isset($options['keepVcs']) || empty($options['keepVcs'])) {
            if (empty($options['context']) || !preg_match('/^Development.*/', $options['context'])) {
                $flagOptions['exclude'][] = '.git';
                $flagOptions['exclude'][] = '.svn';
            }
        }

        $command = array_merge([$this->rsyncCommand], $this->getFlags($flagOptions));
        $command[] = $this->getFullPath($sourceNode, $sourcePath);
        $command[] = $this->getFullPath($destinationNode, $destinationPath);

        $this->shell->executeOrSimulate('mkdir -p '.escapeshellarg($destinationPath), $destinationNode, $deployment);
        $this->shell->executeOrSimulate(implode(' ', $command), $deployment->getNode('localhost'), $deployment);
    }

    /**
     * @param Node $node,... Nodes
     *
     * @return null|Node
     */
    protected function getFirstExternalNode($node)
    {
        $nodes = func_get_args();
        foreach ($nodes as $node) {
            /** @var Node $node */
            if ($node->isLocalhost() === false) {
                return $node;
            }
        }

        return null;
    }

    /**
     * @param array $flagOptions
     *
     * @return array
     */
    protected function getFlags($flagOptions)
    {
        $flags = [];

        foreach ($flagOptions as $key => $value) {
            $prefix = strlen($key) === 1 ? '-' : '--';
            if (is_bool($value)) {
                if ($value) {
                    // of example "--quiet"
                    $flags[] = $prefix.$key;
                }
            } elseif (is_array($value)) {
                foreach ($value as $subKey => $subValue) {
                    if (is_int($subKey)) {
                        // of example "--exclude 'dir'"
                        $flags[] = $prefix.$key.' '.escapeshellarg($subValue);
                    } else {
                        // of example "-o BatchMode='yes'"
                        $flags[] = $prefix.$key.' '.$subKey.'='.$subValue;
                    }
                }
            } elseif (is_string($value)) {
                // of example "--rsh 'ssh -p 22'"
                $flags[] = $prefix.$key.' '.escapeshellarg($value);
            }
        }

        return $flags;
    }

    /**
     * @param Node   $node
     * @param string $path
     *
     * @return string
     */
    protected function getFullPath(Node $node, $path)
    {
        $hostArgument = '';
        if ($node->isLocalhost() === false) {
            if ($node->hasOption('username')) {
                $hostArgument .= $node->getOption('username').'@';
            }
            $hostArgument .= $node->getHostname().':';
        }

        return escapeshellarg($hostArgument.rtrim($path, '/').'/');
    }
}
