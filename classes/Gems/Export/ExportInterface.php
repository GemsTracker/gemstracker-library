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
 * @version    $Id: ExportInterface.php 128 2011-08-12 12:33:14Z 175780 $
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
 * @since      Class available since version 1.5
 */
interface Gems_Export_ExportInterface
{
    /**
     * Return an array of Form Elements for this specific export
     *
     * @param type $form
     * @param type $data
     * @return array of Zend_Form_Element
     */
    public function getFormElements(&$form, &$data);

    /**
     * Sets the default form values when this export type is first chosen
     *
     * @return array
     */
    public function getDefaults();

    /**
     * Returns the unique name for this class
     *
     * It will be used for handling this export's specific options
     *
     * @return string
     */
    public function getName();

    /**
     * This method handles the export with the given options
     *
     * The method takes care of rendering the right script by using $this->export->controller to
     * access the controller object.
     *
     * @param array                     $data        The formdata
     * @param Gems_Tracker_Survey       $survey      The survey object we are exporting
     * @param array                     $answers     The array of answers
     * @param MUtil_Model_ModelAbstract $answerModel The modified answermodel that includes info about extra attributes
     * @param string                    $language    The language used / to use for the export
     */
    public function handleExport($data, $survey, $answers, $answerModel, $language);
}