<?php
namespace Lightwerk\SurfTasks\Tests\Unit\Task\TYPO3\CMS;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Surf".            *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use org\bovigo\vfs\vfsStream;
use Lightwerk\SurfTasks\Service\CommandProviderService;

/**
 * Creates the command for an extbase commando
 *
 * @package Lightwerk\SurfTasks
 */
class CommandProviderServiceTest extends \TYPO3\Flow\Tests\UnitTestCase
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
        $deployment = $this->createMock('TYPO3\Surf\Domain\Model\Deployment', ['getWorkspacePath'], [], '', false);
        $deployment->expects($this->once())->method('getWorkspacePath')->will($this->returnValue($rootUrl));
        $application = new \TYPO3\Surf\Domain\Model\Application('bar');
        $commandProviderService = $this->getAccessibleMock(CommandProviderService::class, ['foo']);
        $webRoot = $commandProviderService->_call('getWebDir', $deployment, $application);
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
        $deployment = $this->createMock('TYPO3\Surf\Domain\Model\Deployment', ['getWorkspacePath'], [], '', false);
        $deployment->expects($this->once())->method('getWorkspacePath')->will($this->returnValue($rootUrl));
        $application = new \TYPO3\Surf\Domain\Model\Application('bar');
        $commandProviderService = $this->getAccessibleMock(CommandProviderService::class, ['foo']);
        $webRoot = $commandProviderService->_call('getWebDir', $deployment, $application);
        $this->assertSame('', $webRoot);
    }

    /**
     * @test
     */
    public function isCoreapiInstalledReturnsTrueIfExtensionDirectoryExist()
    {
        $structure = [
            'typo3conf' => [
                'ext' => [
                    'coreapi' => []
                ]
            ]
        ];
        vfsStream::setup('root', null, $structure);
        $rootUrl = vfsStream::url('root');

        $deployment = $this->createMock('TYPO3\Surf\Domain\Model\Deployment', ['getWorkspacePath'], [], '', false);
        $deployment->expects($this->once())->method('getWorkspacePath')->will($this->returnValue($rootUrl));
        $application = new \TYPO3\Surf\Domain\Model\Application('bar');
        $commandProviderService = $this->getAccessibleMock(CommandProviderService::class, ['getWebDir']);
        $commandProviderService->expects($this->once())->method('getWebDir')->will($this->returnValue(''));
        $reflection = new \ReflectionClass($commandProviderService);
        $method = $reflection->getMethod('isCoreapiInstalled');
        $method->setAccessible(true);
        $this->assertTrue($method->invokeArgs($commandProviderService, [$deployment, $application]));
    }
}
