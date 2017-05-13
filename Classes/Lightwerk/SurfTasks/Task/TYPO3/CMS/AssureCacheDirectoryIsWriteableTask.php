<?php

namespace Lightwerk\SurfTasks\Task\TYPO3\CMS;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Lightwerk.SurfTasks".   *
 *                                                                        *
 *                                                                        */

class AssureCacheDirectoryIsWriteableTask extends ExtbaseCommandTask
{
    /**
     * @param array $options
     * @return string
     */
    protected function getCoreapiArguments(array $options): string
    {
        return 'cacheapi:assurecachedirectoryiswriteable';
    }

    /**
     * @param array $options
     * @return string
     */
    protected function getTypo3ConsoleArguments(array $options): string
    {
        return '';
    }
}
