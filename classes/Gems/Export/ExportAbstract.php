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
 * Short description of file
 *
 * @package    Gems
 * @subpackage Export
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * This is a helper class to make implementing exports easier
 *
 * The setBatch method will only be used when running batch exports. To do so
 * you must implement the \Gems_Export_ExportBatchInterface
 *
 * @package    Gems
 * @subpackage Export
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
abstract class Gems_Export_ExportAbstract extends \MUtil_Translate_TranslateableAbstract
    implements \Gems_Export_ExportInterface
{
    /**
     * Holds the current batch if there is any
     *
     * @var \Gems_Task_TaskRunnerBatch
     */
    protected $_batch = null;

    /**
     * Variable needed to access the controller functions
     *
     * $this->export->controller
     *
     * @var \Gems_Export
     */
    public $export;

    /**
     *
     * @var \Gems_Loader
     */
    public $loader;

    /**
     * @var \Zend_View
     */
    public $view;

    /**
     * Creates a valid filename for a survey
     *
     * @param \Gems_Tracker_Survey $survey
     * @param string $extension Extension, including the dot
     * @return string
     */
    protected function getSurveyFilename(\Gems_Tracker_Survey $survey, $extension = '.dat')
    {
        // Change all slashes, colons and spaces to underscores
        $filename = str_replace(array('/', '\\', ':', ' '), '_', $survey->getName());
        // Remove dot if it starts with one
        $filename = trim($filename, '.');
        return $filename . $extension;
    }

    /**
     * Set the batch to be used by this source
     *
     * Use $this->hasBatch to check for existence
     *
     * @param \Gems_Task_TaskRunnerBatch $batch
     */
    public function setBatch(\Gems_Task_TaskRunnerBatch $batch)
    {
        $this->_batch = $batch;
    }
}