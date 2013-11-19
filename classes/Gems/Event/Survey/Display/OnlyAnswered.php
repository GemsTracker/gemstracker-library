<?php

/**
 * Copyright (c) 2012, Erasmus MC
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
 *
 * @package    Gems
 * @subpackage Events
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Display only those questions that have an answer
 *
 * @package    Gems
 * @subpackage Events
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.6
 */
class Gems_Event_Survey_Display_OnlyAnswered extends Gems_Event_SurveyAnswerFilterAbstract
{
    /**
     * This function is called in addBrowseTableColumns() to filter the names displayed
     * by AnswerModelSnippetGeneric.
     *
     * @see Gems_Tracker_Snippets_AnswerModelSnippetGeneric
     *
     * @param MUtil_Model_TableBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     * @param array $currentNames The current names in use (allows chaining)
     * @return array Of the names of labels that should be shown
     */
    public function filterAnswers(MUtil_Model_TableBridge $bridge, MUtil_Model_ModelAbstract $model, array $currentNames)
    {
        $repeater = $model->loadRepeatable();
        $table    = $bridge->getTable();
        $table->setRepeater($repeater);

        if (! $repeater->__start()) {
            return $currentNames;
        }

        $keys = array();
        while ($row = $repeater->__next()) {
            // Add the keys that contain values.
            // We don't care about the values in the array.
            $keys += $this->array_filter($row->getArrayCopy(), $model);
        }

        $results = array_intersect($currentNames, array_keys($keys), array_keys($this->token->getRawAnswers()));
        // MUtil_Echo::track($results);

        $results = $this->restoreHeaderPositions($model, $results);

        if ($results) {
            return $results;
        }

        return $this->getHeaders($model, $currentNames);
    }

    /**
     * A pretty name for use in dropdown selection boxes.
     *
     * @return string Name
     */
    public function getEventName()
    {
        return $this->translate->_('Display only the questions with an answer.');
    }
    
    /**
     * Strip elements from the array that are considered empty
     * 
     * Empty is NULL or empty string, values of 0 are NOT empty unless they are a checkbox
     *  
     * @param type $inputArray
     * @param type $model
     * @return boolean
     */
    public function array_filter($inputArray, $model)
    {
        $outputArray = array();
        foreach ($inputArray as $key => $value) {
            // Null and empty string are skipped
            if (is_null($value) || $value === '') {
                continue;
            }
            // Maybe do a check on multiOptions for checkboxes etc. to disable some 0 values $model->get($key, 'multiOptions');
            if ($value == '0' && $options = $model->get($key, 'multiOptions')) {
                if (count($options) == 2) {
                    // Probably a checkbox (multi flexi in limesurvey)
                    continue;
                }
            }
            $outputArray[$key] = $value;
        }
        return $outputArray;
    }
}
