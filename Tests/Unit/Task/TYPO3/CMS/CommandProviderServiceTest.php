<?php
namespace Lightwerk\SurfTasks\Tests\Unit\Task\TYPO3\CMS;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Surf".            *
 *                                                                        */

use Lightwerk\SurfTasks\Tests\Unit\UnitTestCase;
use TYPO3\Flow\Annotations as Flow;
use org\bovigo\vfs\vfsStream;
use Lightwerk\SurfTasks\Service\CommandProviderService;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;

/**
 * Creates the command for an extbase commando
 *
 * @package Lightwerk\SurfTasks
 */
class CommandProviderServiceTest extends UnitTestCase
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
        $deployment = $this->getDeploymentForFileStructure($structure);
        $application = new Application('bar');
        $commandProviderService = $this->getAccessibleMock(CommandProviderService::class, ['foo']);
        $webRoot = $commandProviderService->_call('getWebDir', $deployment, $application);
        $this->assertSame('foo/', $webRoot);
    }

    /**
     * @test
     */
    public function getWebDirReturnsEmptyStringIfNoComposerWebDirIsFound()
    {
        $deployment = $this->getDeploymentForFileStructure([]);
        $application = new Application('bar');
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
        $deployment = $this->getDeploymentForFileStructure($structure);
        $application = new Application('bar');
        $commandProviderService = $this->getAccessibleMock(CommandProviderService::class, ['getWebDir']);
        $commandProviderService->expects($this->once())->method('getWebDir')->will($this->returnValue(''));

        $method = $this->getAccessiblePrivateMethodForObject('isCoreapiInstalled', $commandProviderService);

        $this->assertTrue($method->invokeArgs($commandProviderService, [$deployment, $application]));
    }

    /**
     * @param array $structure
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getDeploymentForFileStructure(array $structure)
    {
        vfsStream::setup('root', null, $structure);
        $rootUrl = vfsStream::url('root');
        $deployment = $this->createMock(Deployment::class, ['getWorkspacePath'], [], '', false);
        $deployment->expects($this->once())->method('getWorkspacePath')->will($this->returnValue($rootUrl));
        return $deployment;
    }
}
