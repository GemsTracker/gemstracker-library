<?php
/**
 * @package    Gems
 * @subpackage Export
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * The export interface
 *
 * Exporting survey-data can be done for various sorts of output formats, this interface
 * describes the methods needed to implement an output format
 *
 * @package    Gems
 * @subpackage Export
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.1
 */
interface Gems_Export_ExportBatchInterface extends \Gems_Export_ExportInterface
{
    /**
     * This method handles setting up all needed steps for the batch export
     *
     * Normally this will initialize the file to download and set up as much
     * steps as needed and the final job to close the file.
     * 
     * To offer a file for download, add a message with the key 'file' to the
     * batch. The message must be an array of 'headers' that contains an array
     * of headers to set for the download and 'file' that holds the path to the 
     * file relative to GEMS_ROOT_DIR . '/var/tmp/'
     *
     * @param array                     $filter      The filter to use
     * @param string                    $language    The language used / to use for the export
     * @param array                     $data        The formdata
     */
    public function handleExportBatch($filter, $language, $data);
        
    /**
     * Set the batch to be used by this source
     *
     * Use $this->hasBatch to check for existence
     *
     * @param \Gems_Task_TaskRunnerBatch $batch
     */
    public function setBatch(\Gems_Task_TaskRunnerBatch $batch);
}