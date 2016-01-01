<?php
namespace Lightwerk\SurfTasks\Tests\Unit\Task\Database;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Surf".            *
 *                                                                        */

use Lightwerk\SurfTasks\Task\Database\AbstractTask;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Tests\UnitTestCase;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Node;

/**
 * Creates the command for an extbase commando
 *
 * @package Lightwerk\SurfTasks
 */
class AbstractTaskTest extends UnitTestCase
{

    /**
     * @var AbstractTask
     */
    protected $abstractTask;

    /**
     * @var Node
     */
    protected $node;

    /**
     * @var Deployment
     */
    protected $deployment;

    /**
     * @var Application
     */
    protected $application;

    /**
     * Returns a mock object which allows for calling protected methods and access
     * of protected properties.
     *
     * Needed to be overwritten to be able to pass $mockedMethods.
     *
     * @param string $originalClassName Full qualified name of the original class
     * @param array $arguments
     * @param string $mockClassName
     * @param boolean $callOriginalConstructor
     * @param boolean $callOriginalClone
     * @param boolean $callAutoload
     * @param array $mockedMethods mocked methods
     * @return \PHPUnit_Framework_MockObject_MockObject
     * @api
     */
    protected function getAccessibleMockForAbstractClass(
        $originalClassName,
        array $arguments = array(),
        $mockClassName = '',
        $callOriginalConstructor = true,
        $callOriginalClone = true,
        $callAutoload = true,
        $mockedMethods = []
    ) {
        return $this->getMockForAbstractClass($this->buildAccessibleProxy($originalClassName), $arguments, $mockClassName, $callOriginalConstructor, $callOriginalClone, $callAutoload, $mockedMethods);
    }

    /**
     * @return void
     */
    public function setUp()
    {
        $this->abstractTask = $this->getAccessibleMockForAbstractClass(AbstractTask::class);
        $this->node = new Node('Foo');
        $this->deployment = new Deployment('Bar');
        $this->application = new Application('FooBar');
    }

    /**
     * @return void
     */
    public function tearDown()
    {
        $this->abstractTask = null;
        $this->node = null;
        $this->deployment = null;
        $this->application = null;
    }

    /**
     * @return void
     * @test
     * @expectedException \TYPO3\Surf\Exception\InvalidConfigurationException
     * @dataProvider insufficientConfigurationDataProvider
     */
    public function getCredentialsThrowsExceptionForInsufficientConfiguration($config)
    {
        $this->abstractTask->_call('getCredentials', $this->node, $this->deployment, $config, $this->application);
    }

    /**
     * Data Provider for getCredentialsThrowsExceptionForInsufficientConfiguration
     *
     * @return array
     */
    public function insufficientConfigurationDataProvider()
    {
        return [
            'no db part' => [
                []
            ],
            'empty db part' => [
                ['db' => []]
            ],
            'insufficient credentialsSource' => [
                [
                    'db' => [
                        'credentialsSource' => 'I am insufficient'
                    ]
                ]
            ]
        ];
    }

    /**
     * @return void
     * @test
     */
    public function getCredentialsReturnsConfiguredDatabase()
    {
        $config = [
            'db' => [
                'user' => 'root',
                'password' => 'root'
            ]
        ];
        $credentials = $this->abstractTask->_call('getCredentials', $this->node, $this->deployment, $config, $this->application);
        $this->assertSame($config['db'], $credentials);
    }

    /**
     * @return void
     * @test
     */
    public function getCredentialsCallsGetCredentialsFromTypo3CmsForCredentialSourceTypo3Cms()
    {
        $this->abstractTask = $this->getAccessibleMockForAbstractClass(AbstractTask::class, [], '', true, true, true, ['getCredentialsFromTypo3Cms']);
        $this->abstractTask->expects(($this->once()))->method('getCredentialsFromTypo3Cms')->will($this->returnValue([]));
        $config = [
            'db' => [
                'credentialsSource' => 'TYPO3\\CMS'
            ]
        ];
        $this->abstractTask->_call('getCredentials', $this->node, $this->deployment, $config, $this->application);
    }

    /**
     * @return void
     * @test
     */
    public function getDumpFileReturnsPathWithConfiguredDumpPath()
    {
        $credentials = [
            'database' => 'dummybase'
        ];
        $config = [
            'dumpPath' => '/foo/bar'
        ];
        $this->assertEquals('/foo/bar/dummybase.sql.gz', $this->abstractTask->_call('getDumpFile', $config, $credentials));
    }

    /**
     * @return void
     * @test
     */
    public function getDumpFileReturnsPathWithDefaultPath()
    {
        $credentials = [
            'database' => 'dummybase'
        ];
        $config = [];
        $this->assertEquals('/tmp/dummybase.sql.gz', $this->abstractTask->_call('getDumpFile', $config, $credentials));
    }

    /**
     * @param array $credentials
     * @param bool $appendDatabase
     * @param string $expectation
     * @return void
     * @test
     * @dataProvider mysqlArgumentsDataProvider
     */
    public function getMysqlArgumentsReturnsImplodedStringOfArrayParts($credentials, $appendDatabase, $expectation)
    {
        $this->assertEquals($expectation, $this->abstractTask->_call('getMysqlArguments', $credentials, $appendDatabase));
    }

    /**
     * Data Provider for getMysqlArgumentsReturnsImplodedStringOfArrayParts
     *
     * @return array
     */
    public function mysqlArgumentsDataProvider()
    {
        return [
            'only user' => [
                [
                    'user' => 'foo'
                ],
                false,
                '--user=\'foo\''
            ],
            'all supported' => [
                [
                    'user' => 'foo',
                    'password' => 'pw',
                    'host' => 'localhost',
                    'socket' => 'socket',
                    'port' => 1234
                ],
                false,
                '--user=\'foo\' --password=\'pw\' --host=\'localhost\' --socket=\'socket\' --port=\'1234\''
            ],
            'order does not matter' => [
                [
                    'port' => 1234,
                    'user' => 'foo',
                    'host' => 'localhost',
                    'socket' => 'socket',
                    'password' => 'pw'
                ],
                false,
                '--user=\'foo\' --password=\'pw\' --host=\'localhost\' --socket=\'socket\' --port=\'1234\''
            ],
            'unsupported keys are ignored' => [
                [
                    'user' => 'foo',
                    'password' => 'pw',
                    'host' => 'localhost',
                    'socket' => 'socket',
                    'port' => 1234,
                    'foo' => 'bar'
                ],
                false,
                '--user=\'foo\' --password=\'pw\' --host=\'localhost\' --socket=\'socket\' --port=\'1234\''
            ],
            'database can be appended' => [
                [
                    'user' => 'foo',
                    'password' => 'pw',
                    'host' => 'localhost',
                    'socket' => 'socket',
                    'port' => 1234,
                    'database' => 'dummybase'
                ],
                true,
                '--user=\'foo\' --password=\'pw\' --host=\'localhost\' --socket=\'socket\' --port=\'1234\' \'dummybase\''
            ],
        ];
    }
}
