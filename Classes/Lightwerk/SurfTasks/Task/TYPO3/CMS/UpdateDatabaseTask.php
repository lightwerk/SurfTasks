<?php

namespace Lightwerk\SurfTasks\Task\TYPO3\CMS;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "Lightwerk.SurfTasks".   *
 *                                                                        */

/**
 * Updates the database schema.
 */
class UpdateDatabaseTask extends ExtbaseCommandTask
{
    /**
     * @param array $options
     * @return string
     */
    protected function getCoreapiArguments(array $options)
    {
        // Actions:
        // * 1 = ACTION_UPDATE_CLEAR_TABLE
        // * 2 = ACTION_UPDATE_ADD
        // * 3 = ACTION_UPDATE_CHANGE
        // * 4 = ACTION_UPDATE_CREATE_TABLE
        //   5 = ACTION_REMOVE_CHANGE
        //   6 = ACTION_REMOVE_DROP
        //   7 = ACTION_REMOVE_CHANGE_TABLE
        //   8 = ACTION_REMOVE_DROP_TABLE
        $actions = !empty($options['updateDatabaseActions']) ? $options['updateDatabaseActions'] : '1,2,3,4';
        return 'databaseapi:databasecompare ' . escapeshellarg($actions);
    }

    /**
     * @param array $options
     * @return string
     */
    protected function getTypo3ConsoleArguments(array $options)
    {
        // Actions:
        // * FIELD_ADD
        // * FIELD_CHANGE
        //   FIELD_PREFIX
        //   FIELD_DROP
        // * TABLE_ADD
        // * TABLE_CHANGE
        //   TABLE_PREFIX
        //   TABLE_DROP
        $actions = !empty($options['updateDatabaseActions']) ? $options['updateDatabaseActions'] : 'field.add, field.change, table.add, table.change';
        return 'database:updateschema' . escapeshellarg($actions);
    }
}
