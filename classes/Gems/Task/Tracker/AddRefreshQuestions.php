<?php

/**
 * Copyright (c) 2014, Erasmus MC
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
 * @subpackage Task_Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 * @package    Gems
 * @subpackage Task_Tracker
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.3
 */
class Gems_Task_Tracker_AddRefreshQuestions extends \MUtil_Task_TaskAbstract
{
    /**
     * @var \Gems_Loader
     */
    protected $loader;
    
    /**
     * @var Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     */
    public function execute($sourceId = null, $sourceSurveyId = null, $surveyId = null)
    {
        $batch = $this->getBatch();

        if ($surveyId) {
            $survey = $this->loader->getTracker()->getSurvey($surveyId);
        } else {
            $survey = $this->loader->getTracker()->getSurveyBySourceId($sourceSurveyId, $sourceId);
        }

        if (! $survey->isActive()) {
            return;
        }

        // Now save the questions
        $answerModel = $survey->getAnswerModel('en');

        foreach ($answerModel->getItemsOrdered() as $order => $name) {
            if (true === $answerModel->get($name, 'survey_question')) {
                $batch->addTask('Tracker_RefreshQuestion', $surveyId, $name, $order);
            }
        }
        
        // If we have a response database, create a view on the answers
        if ($this->project->hasResponseDatabase()) {
            $this->replaceCreateView($this->getViewName($survey), $answerModel);            
        }
    }
    
    /**
     * Get a name for the view
     * 
     * @param \Gems_Tracker_Survey $survey
     * @return string
     */
    protected function getViewName(\Gems_Tracker_Survey $survey)
    {
        return 'T' . $survey->getSurveyId();
    }
    
    /**
     * Handles creating or replacing the view for this survey
     * 
     * @param string                     $viewName
     * @param \MUtil_Model_ModelAbstract $answerModel
     */
    protected function replaceCreateView($viewName, \MUtil_Model_ModelAbstract $answerModel) {
        $responseDb = $this->project->getResponseDatabase();
        $fieldSql   = '';

        foreach ($answerModel->getItemsOrdered() as $name) {
            if (true === $answerModel->get($name, 'survey_question') && // It should be a question
                    !in_array($name, array('submitdate', 'startdate', 'datestamp')) && // Leave out meta info
                    !$answerModel->is($name, 'type', \MUtil_Model::TYPE_NOVALUE)) {         // Only real answers
                $fieldSql .= ',MAX(IF(gdr_answer_id = ' . $responseDb->quote($name) . ', gdr_response, NULL)) AS ' . $responseDb->quote($name);
            }
        }

        if ($fieldSql > '') {
            $createViewSql = 'CREATE OR REPLACE VIEW ' . $responseDb->quote($viewName) . ' AS SELECT gdr_id_token';
            $createViewSql .= $fieldSql;
            $createViewSql .= "FROM gemsdata__responses join gems__tokens on (gto_id_token=gdr_id_token and gto_id_survey=" . $surveyId . ") GROUP BY gdr_id_token;";

            $responseDb->query($createViewSql)->execute();
        }
    }
    
}
