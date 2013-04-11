<?php
/**
 * Copyright (c) 2011, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
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
interface Gems_Export_ExportBatchInterface extends Gems_Export_ExportInterface
{
    /**
     * This method handles setting up all needed stept for the batch export
     *
     * Normally this will initialize the file to download and set up as much
     * steps as needed and the final job to close the file.
     *
     * @param Gems_Task_TaskRunnerBatch $batch       The batch to start
     * @param array                     $filter      The filter to use
     * @param string                    $language    The language used / to use for the export
     * @param array                     $data        The formdata
     */
    public function handleExportBatch($batch, $filter, $language, $data);
    
    /**
     * Executes a step in exporting the data, this should be as large as possible
     * without hitting max request time limits
     * 
     * @param Gems_Task_TaskRunnerBatch $batch       The batch to start
     * @param array                     $data        The formdata
     * @param array                     $filter      The filter to use
     * @param string                    $language    The language used / to use for the export
     */
    public function handleExportBatchStep($batch, $data, $filter, $language);
            
    /**
     * Final step in batch export, perform cleanup / finalize the file
     * 
     * @param Gems_Task_TaskRunnerBatch $batch
     * @param array $data
     */
    public function handleExportBatchFinalize($batch, $data);
}