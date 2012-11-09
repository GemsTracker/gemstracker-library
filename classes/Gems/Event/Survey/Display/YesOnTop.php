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
 * @version    $id: OnlyAnswered.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 * Display those questions that are answered with 'yes' op top
 *
 * Questions names that are the same as the yes question but with a longer name
 * separated by an '_' are moved with the question, as are header questions
 * (which may be doubled when not all question come out on top).
 *
 * @package    Gems
 * @subpackage Events
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.6
 */
class Gems_Event_Survey_Display_YesOnTop extends Gems_Event_SurveyAnswerFilterAbstract
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
        if (! $this->token->isCompleted()) {
            return $currentNames;
        }

        $answers  = $this->token->getRawAnswers();
        $onTop    = array();

        // MUtil_Echo::track($answers);

        foreach($answers as $name => $value) {
            if ($value === 'Y') {
                $onTop[$name] = $name;
            } else {
                $nameParts = explode('_', $name);

                if (count($nameParts) > 1) {
                    if (isset($onTop[$nameParts[0]])) {
                        $onTop[$name] = $name;
                    }
                }
            }
        }

        $currentNames = array_combine($currentNames, $currentNames);
        // MUtil_Echo::track($onTop, $onTop + $currentNames, $currentNames);

        return $this->restoreHeaderPositions($model, $onTop + $currentNames);
    }

    /**
     * A pretty name for use in dropdown selection boxes.
     *
     * @return string Name
     */
    public function getEventName()
    {
        return $this->translate->_('Yes answers on top.');
    }
}
