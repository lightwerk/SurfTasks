<?php
namespace Lightwerk\SurfTasks\Tests\Unit\Task\TYPO3\CMS;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Surf".            *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use org\bovigo\vfs\vfsStream;

/**
 * Creates the command for an extbase commando
 *
 * @package Lightwerk\SurfTasks
 */
class ExtbaseCommandTaskTest extends \TYPO3\Flow\Tests\UnitTestCase
{

    /**
     * @test
     */
    public function getWebDirReturnsWebDirOfComposerIfDefined()
    {
        $structure = [
            'composer.json' => '
{
  "extra": {
    "typo3/cms": {
      "cms-package-dir": "{$vendor-dir}/typo3/cms",
      "web-dir": "foo"
    }
  }
}'
        ];
        vfsStream::setup('root', null, $structure);
        $rootUrl = vfsStream::url('root');
        $deployment = $this->getMock('TYPO3\Surf\Domain\Model\Deployment', ['getWorkspacePath'], [], '', false);
        $deployment->expects($this->once())->method('getWorkspacePath')->will($this->returnValue($rootUrl));
        $application = new \TYPO3\Surf\Domain\Model\Application('bar');
        $extbaseCommandoTask = $this->getAccessibleMockForAbstractClass('Lightwerk\SurfTasks\Task\TYPO3\CMS\ExtbaseCommandTask');
        $webRoot = $extbaseCommandoTask->_call('getWebDir', $deployment, $application);
        $this->assertSame('foo/', $webRoot);
    }

    /**
     * @test
     */
    public function getWebDirReturnsEmptyStringIfNoComposerWebDirIsFound()
    {
        $structure = [];
        vfsStream::setup('root', null, $structure);
        $rootUrl = vfsStream::url('root');
        $deployment = $this->getMock('TYPO3\Surf\Domain\Model\Deployment', ['getWorkspacePath'], [], '', false);
        $deployment->expects($this->once())->method('getWorkspacePath')->will($this->returnValue($rootUrl));
        $application = new \TYPO3\Surf\Domain\Model\Application('bar');
        $extbaseCommandoTask = $this->getAccessibleMockForAbstractClass('Lightwerk\SurfTasks\Task\TYPO3\CMS\ExtbaseCommandTask');
        $webRoot = $extbaseCommandoTask->_call('getWebDir', $deployment, $application);
        $this->assertSame('', $webRoot);
    }

    /**
     * @test
     */
    public function buildCommandReturnsTypo3CliCommandIfExtensionFolderExists()
    {
        $structure = [
            'typo3conf' => [
                'ext' => [
                    'extensionName' => []
                ]
            ]
        ];
        vfsStream::setup('root', null, $structure);
        $rootUrl = vfsStream::url('root');
        $deployment = $this->getMock('TYPO3\Surf\Domain\Model\Deployment', ['getWorkspacePath'], [], '', false);
        $deployment->expects($this->any())->method('getWorkspacePath')->will($this->returnValue($rootUrl));
        $application = new \TYPO3\Surf\Domain\Model\Application('bar');
        $extbaseCommandoTask = $this->getAccessibleMockForAbstractClass('Lightwerk\SurfTasks\Task\TYPO3\CMS\ExtbaseCommandTask');
        $command = $extbaseCommandoTask->_call('buildCommand', $deployment, $application, 'extensionName', 'arguments');
        $this->assertSame('typo3/cli_dispatch.phpsh extbase arguments', $command);
    }

    /**
     * @test
     */
    public function buildCommandReturnsEmptyStringIfExtensionFolderNotExists()
    {
        $structure = [];
        vfsStream::setup('root', null, $structure);
        $rootUrl = vfsStream::url('root');
        $deployment = $this->getMock('TYPO3\Surf\Domain\Model\Deployment', ['getWorkspacePath'], [], '', false);
        $deployment->expects($this->any())->method('getWorkspacePath')->will($this->returnValue($rootUrl));
        $application = new \TYPO3\Surf\Domain\Model\Application('bar');
        $extbaseCommandoTask = $this->getAccessibleMockForAbstractClass('Lightwerk\SurfTasks\Task\TYPO3\CMS\ExtbaseCommandTask');
        $command = $extbaseCommandoTask->_call('buildCommand', $deployment, $application, 'extensionName', 'arguments');
        $this->assertSame('', $command);
    }


}
