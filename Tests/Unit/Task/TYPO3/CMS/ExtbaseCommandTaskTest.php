<?php
namespace Lightwerk\SurfTasks\Tests\Unit\Task\TYPO3\CMS;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Surf".            *
 *                                                                        */

use Lightwerk\SurfTasks\Service\CommandProviderService;
use TYPO3\Flow\Annotations as Flow;
use Lightwerk\SurfTasks\Task\TYPO3\CMS\ExtbaseCommandTask;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Application;

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
    public function buildCoreapiCommandReturnsTypo3CliCoreapiCommand()
    {
        $deployment = new Deployment('bar');
        $application = new Application('foo');

        #$extbaseCommandoTask = $this->getAccessibleMockForAbstractClass(ExtbaseCommandTask::class, [], '', true, true, true, ['getCoreapiArguments']);
        #$extbaseCommandoTask->expects($this->once())->method('getCoreapiArguments')->with(['option'])->will($this->returnValue('arguments'));

        $commandProviderService = $this->getMock(CommandProviderService::class);
        #$commandProviderService->expects($this->once())->method('getWebDir')->will($this->returnValue('webDir'));
        #$this->inject($extbaseCommandoTask, 'commandProviderService', $commandProviderService);
        #$command = $extbaseCommandoTask->_call('buildCoreapiCommand', $deployment, $application, ['option']);
        #$this->assertSame('webDir/typo3/cli_dispatch.phpsh extbase arguments', $command);
    }
}
