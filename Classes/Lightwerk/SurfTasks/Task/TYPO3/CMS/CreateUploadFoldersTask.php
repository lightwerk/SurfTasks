<?php

namespace Lightwerk\SurfTasks\Task\TYPO3\CMS;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Lightwerk.SurfTasks".   *
 *                                                                        *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Surf\Domain\Model\Application;
use TYPO3\Surf\Domain\Model\Deployment;
use TYPO3\Surf\Domain\Model\Node;
use TYPO3\Surf\Exception\TaskExecutionException;

/**
 * Create upload folder for all installed TYPO3 extensions.
 */
class CreateUploadFoldersTask extends ExtbaseCommandTask
{
    /**
     * @param array $options
     * @return string
     */
    protected function getCoreapiArguments(array $options)
    {
        return 'extensionapi:createuploadfolders';
    }

    /**
     * @param array $options
     * @return string
     */
    protected function getTypo3ConsoleArguments(array $options)
    {
        return 'extension:setupactive';
    }
}
