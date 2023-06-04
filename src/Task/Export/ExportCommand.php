<?php

/**
 * @package    Gems
 * @subpackage Task_Export
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Task\Export;

/**
 * Executes any command in an Export class for a given $exportType
 *
 * @package    Gems
 * @subpackage Task_Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.3
 */
class ExportCommand extends \Gems\Task\TaskAbstract
{
    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     *
     * @param string $exportType Name of export class
     * @param string $command    Command to call in export class
     * @param array  $filter     The filter to use
     * @param string $language   The language used / to use for the export
     * @param array  $data       The formdata
     */
    public function execute($exportType = null, $command = null, $params = null)
    {
        $params = array_slice(func_get_args(), 2);
        $export = $this->loader->getExport()->getExport($exportType, null, $this->_batch);

        if ($messages = call_user_func_array(array($export, $command), $params)) {
            foreach ($messages as $message) {
                $this->_batch->addMessage($command . ': ' . $message);
            }
        }
    }
}