<?php

namespace Lightwerk\SurfTasks\Task\TYPO3\CMS;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Lightwerk.SurfTasks".   *
 *                                                                        */

/**
 * Clears caches in the database and the filesystem.
 */
class ClearCacheTask extends ExtbaseCommandTask
{
    /**
     * @param array $options
     * @return string
     */
    protected function getCoreapiArguments(array $options)
    {
        return 'cacheapi:clearallcaches -hard true';
    }

    /**
     * @param array $options
     * @return string
     */
    protected function getTypo3ConsoleArguments(array $options)
    {
        return 'cache:flush --force';
    }
}
