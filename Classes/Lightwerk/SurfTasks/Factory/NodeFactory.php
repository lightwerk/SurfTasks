<?php

namespace Lightwerk\SurfTasks\Factory;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Lightwerk.SurfTasks".  *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Exception\InvalidConfigurationException;

/**
 * Node Factory.
 *
 * @Flow\Scope("singleton")
 */
class NodeFactory
{
    /**
     * @param array $configuration
     * @return Node
     * @throws InvalidConfigurationException
     */
    public function getNodeByArray(array $configuration)
    {
        $this->assureValidConfiguration($configuration);
        $node = new Node($configuration['name']);
        foreach ($configuration as $key => $value) {
            if ($key === 'name') {
                continue;
            }
            $method = 'set' . ucfirst($key);
            if (method_exists($node, $method)) {
                $node->$method($value);
            } else {
                $node->setOption($key, $value);
            }
        }

        return $node;
    }

    /**
     * @param array $configuration
     * @throws InvalidConfigurationException
     */
    private function assureValidConfiguration(array $configuration)
    {
        if (empty($configuration['name'])) {
            throw new InvalidConfigurationException('Name is not given for node', 1437472548);
        }
        if (empty($configuration['hostname'])) {
            throw new InvalidConfigurationException('Hostname is not given for node', 1437472549);
        }
    }
}
