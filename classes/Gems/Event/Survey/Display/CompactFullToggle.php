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
 * @version    $Id: OnlyAnswered.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 * Display only those questions that have an answer
 *
 * @package    Gems
 * @subpackage Events
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.1
 */
class Gems_Event_Survey_Display_CompactFullToggle extends Gems_Event_SurveyAnswerFilterAbstract
{    
     public $IncludeLength = 5;
     public $IncludeStarts = array('score');
     
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

        // Filter unless option 'fullanswers' is true, can be set as get or post var.
        $requestFullAnswers = Zend_Controller_Front::getInstance()->getRequest()->getParam('fullanswers', false);
        if (! $repeater->__start()) {
            return $currentNames;
        }
        
        $keys = array();
        if ($requestFullAnswers !== false) {
            while ($row = $repeater->__next()) {
                // Add the keys that contain values.
                // We don't care about the values in the array.
                $keys += array_filter($row->getArrayCopy());
            }
        } else {            
            foreach ($model->getItemNames() as $name) {
                $start = substr(strtolower($name),0,$this->IncludeLength);
                if (in_array($start, $this->IncludeStarts)) {
                    $keys[$name] = $name;
                }
            }
        }
        
        $results = array_intersect($currentNames, array_keys($keys), array_keys($this->token->getRawAnswers()));
        
        $results = $this->restoreHeaderPositions($model, $results);

        if ($results) {
            return $results;
        }

        return $this->getHeaders($model, $currentNames);
    }
    
    public function getAnswerDisplaySnippets(\Gems_Tracker_Token $token) {
        $snippets = parent::getAnswerDisplaySnippets($token);
        
        array_unshift($snippets, 'Survey_Display_FullAnswerToggleSnippet');
        
        return $snippets;
    }

    /**
     * A pretty name for use in dropdown selection boxes.
     *
     * @return string Name
     */
    public function getEventName()
    {
        return $this->translate->_('Display only the questions whose code starts with `score`.');
    }
}
