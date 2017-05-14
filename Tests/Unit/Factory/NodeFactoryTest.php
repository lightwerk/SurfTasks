<?php
namespace Lightwerk\SurfTasks\Tests\Unit\Factory;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Surf".            *
 *                                                                        */

use Lightwerk\SurfTasks\Factory\NodeFactory;
use Lightwerk\SurfTasks\Tests\Unit\UnitTestCase;
use TYPO3\Flow\Annotations as Flow;

/**
 * Test cases for the node factory
 *
 * @package Lightwerk\SurfTasks
 */
class NodeFactoryTest extends UnitTestCase
{

    /**
     * @var NodeFactory
     */
    private $nodeFactory;

    public function setUp()
    {
        parent::setUp();
        $this->nodeFactory = new NodeFactory();

    }

    public function tearDown()
    {
        parent::tearDown();
        $this->nodeFactory = null;
    }

    /**
     * @return array
     */
    public function invalidConfigurationDataProvider()
    {
        return [
            'no name' => [
                [
                    'foo' => 'bar',
                    'hostname' => 'localhost'
                ]
            ],
            'no hostname' => [
                [
                    'foo' => 'bar',
                    'name' => 'foo'
                ]
            ],
            "neither name nor hostname" => [
                [
                    'foo' => 'bar',
                ]
            ]
        ];
    }

    /**
     * @param array $configuration
     * @test
     * @expectedException \TYPO3\Surf\Exception\InvalidConfigurationException
     * @dataProvider invalidConfigurationDataProvider
     */
    public function assureValidConfigurationThrowsExceptionForInvalidConfiguration($configuration)
    {
        $this->getAccessiblePrivateMethodForObject('assureValidConfiguration', $this->nodeFactory)
            ->invokeArgs($this->nodeFactory, [$configuration]);
    }

    /**
     * https://github.com/sebastianbergmann/phpunit-documentation/issues/171#issuecomment-56284578
     * @test
     */
    public function assureValidConfigurationPassesForInvalidConfiguration()
    {
        $validConfiguration = [
            'name' => 'foo',
            'hostname' => 'localhost'
        ];
        $this->getAccessiblePrivateMethodForObject('assureValidConfiguration', $this->nodeFactory)
            ->invokeArgs($this->nodeFactory, [$validConfiguration]);
        $this->addToAssertionCount(1);
    }

    /**
     * @test
     */
    public function getNodeByArrayReturnsFullyEnrichedNodeObject()
    {
        $expectation = [
            'name' => 'node name',
            'hostname' => 'localhost',
            'foo' => 'bar'
        ];
        $node = $this->nodeFactory->getNodeByArray($expectation);
        $result = [
            'name' => $node->getName(),
            'hostname' => $node->getHostname(),
            'foo' => $node->getOption('foo')
        ];
        $this->assertSame($expectation, $result);
    }
}
