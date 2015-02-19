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
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Generic controller class for showing and editing respondents
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Default_SurveyMaintenanceAction extends \Gems_Controller_ModelSnippetActionAbstract
{
    /**
     * The parameters used for the autofilter action.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $autofilterParameters = array(
        'columns'   => 'getBrowseColumns',
        'extraSort' => array(
            'gsu_survey_name' => SORT_ASC,
            ),
        );

    /**
     * The parameters used for the create and edit actions.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $createEditParameters = array(
        'cacheTags' => array('surveys', 'tracks'),
    );

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;

    /**
     *
     * @var \Gems_Project_ProjectSettings
     */
    public $project;

    /**
     * Import answers to a survey
     */
    public function answerImportAction()
    {
        $controller   = 'answers';
        $importLoader = $this->loader->getImportLoader();

        $params['defaultImportTranslator'] = $importLoader->getDefaultTranslator($controller);
        $params['formatBoxClass']          = 'browser';
        $params['importer']                = $importLoader->getImporter($controller);
        $params['importLoader']            = $importLoader;
        $params['tempDirectory']           = $importLoader->getTempDirectory();
        $params['importTranslators']       = $importLoader->getTranslators($controller);

        $this->addSnippets('Survey_AnswerImportSnippet', $params);
    }

    /**
     * Import answers to any survey
     */
    public function answerImportsAction()
    {
        $this->answerImportAction();
    }

    /**
     * Check the tokens for a single survey
     */
    public function checkAction()
    {
        $surveyId = $this->_getParam(MUtil_Model::REQUEST_ID);
        $where    = $this->db->quoteInto('gto_id_survey = ?', $surveyId);

        $batch = $this->loader->getTracker()->recalculateTokens('surveyCheck' . $surveyId, $this->loader->getCurrentUser()->getUserId(), $where);

        $title = sprintf($this->_('Checking survey results for the %s survey.'),
                $this->db->fetchOne("SELECT gsu_survey_name FROM gems__surveys WHERE gsu_id_survey = ?", $surveyId));
        $this->_helper->BatchRunner($batch, $title);

        \Gems_Default_SourceAction::addCheckInformation($this->html, $this->translate, $this->_('This task checks all tokens for this survey.'));
    }

    /**
     * Check the tokens for all surveys
     */
    public function checkAllAction()
    {
        $batch = $this->loader->getTracker()->recalculateTokens('surveyCheckAll', $this->loader->getCurrentUser()->getUserId());

        $title = $this->_('Checking survey results for all surveys.');
        $this->_helper->BatchRunner($batch, $title);

        \Gems_Default_SourceAction::addCheckInformation($this->html, $this->translate, $this->_('This task checks all tokens for all surveys.'));
    }

    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel($detailed, $action)
    {
        $survey = null;
        $yesNo  = $this->util->getTranslated()->getYesNo();

        if ($detailed) {
            $surveyId = $this->_getIdParam();

            if ($surveyId) {
                $survey = $this->loader->getTracker()->getSurvey($surveyId);
            }
        }

        $model = new Gems_Model_JoinModel('surveys', 'gems__surveys', 'gus');
        $model->addTable('gems__sources', array('gsu_id_source' => 'gso_id_source'));
        $model->setCreate(false);

        $model->addColumn(
                "CASE WHEN gsu_survey_pdf IS NULL OR CHAR_LENGTH(gsu_survey_pdf) = 0 THEN 0 ELSE 1 END",
                'gsu_has_pdf'
                );
        $model->addColumn(
                "COALESCE(gsu_status, '" . $this->_('OK') . "')",
                'gsu_status_show',
                'gsu_status'
                );
        $model->addColumn(
                "CASE WHEN gsu_surveyor_active THEN '' ELSE 'deleted' END",
                'row_class'
                );

        $model->resetOrder();

        $model->set('gsu_survey_name',        'label', $this->_('Name'),
                'elementClass', 'Exhibitor');
        $model->set('gsu_survey_description', 'label', $this->_('Description'),
                'elementClass', 'Exhibitor',
                'formatFunction', array(__CLASS__, 'formatDescription')
                );
        $model->set('gso_source_name',        'label', $this->_('Source'),
                'elementClass', 'Exhibitor');
        $model->set('gsu_surveyor_active',    'label', $this->_('Active in source'),
                'elementClass', 'Exhibitor',
                'multiOptions', $yesNo
                );
        $model->set('gsu_status_show',        'label', $this->_('Status in source'),
                'elementClass', 'Exhibitor');
        $model->set('gsu_active',             'label', sprintf($this->_('Active in %s'), $this->project->getName()),
                'elementClass', 'Checkbox',
                'multiOptions', $yesNo
                );
        $model->set('gsu_id_primary_group',   'label', $this->_('Group'),
                'description', $this->_('If empty, survey will never show up!'),
                'multiOptions', $this->util->getDbLookup()->getGroups(),
                'validator', new MUtil_Validate_Require(
                        $model->get('gsu_active', 'label'),
                        'gsu_id_primary_group',
                        $model->get('gsu_id_primary_group', 'label')
                        )
                );

        $model->set('gsu_insertable',         'label', $this->_('Insertable'),
                'description', $this->_('Can staff manually insert this survey into a track.'),
                'elementClass', 'Checkbox',
                'multiOptions', $yesNo
                );
        $model->set('gsu_valid_for_length',   'label', $this->_('Add to end date'),
                'description', $this->_('Add to the start date to calculate the end date when inserting.'),
                'filter', 'Int'
                );
        $model->set('gsu_valid_for_unit',     'label', $this->_('End date unit'),
                'description', $this->_('The unit used to calculate the end date when inserting the survey.'),
                'multiOptions', $this->util->getTrackData()->getDateUnitsList(true)
                );
        if ($detailed) {
            $model->set('gsu_duration',       'label', $this->_('Duration description'),
                    'description', $this->_('Text to inform the respondent, e.g. "20 seconds" or "1 minute".')
                    );
            if ($survey instanceof \Gems_Tracker_Survey) {
                $surveyFields = $this->util->getTranslated()->getEmptyDropdownArray() +
                    $survey->getQuestionList($this->locale->getLanguage());
                $model->set('gsu_result_field',   'label', $this->_('Result field'),
                        'multiOptions', $surveyFields);
                // $model->set('gsu_agenda_result',  'label', $this->_('Agenda field'));
            }
        }
        $model->set('gsu_code',               'label', $this->_('Code name'), 'size', 10, 'description', $this->_('Only for programmers.'));

        if ($detailed) {
            $events = $this->loader->getEvents();
            $beforeOptions = $events->listSurveyBeforeAnsweringEvents();
            if (count($beforeOptions) > 1) {
                $model->set('gsu_beforeanswering_event', 'label', $this->_('Before answering'),
                        'multiOptions', $beforeOptions,
                        'elementClass', 'Select');
            }
            $completedOptions = $events->listSurveyCompletionEvents();
            if (count($completedOptions) > 1) {
                $model->set('gsu_completed_event', 'label', $this->_('After completion'),
                        'multiOptions', $completedOptions,
                        'elementClass', 'Select');
            }
            $displayOptions = $events->listSurveyDisplayEvents();
            if (count($displayOptions) > 1) {
                $model->set('gsu_display_event', 'label', $this->_('Answer display'),
                        'multiOptions', $displayOptions,
                        'elementClass', 'Select');
            }
        }

        return $model;
    }

    /**
     * Strip all the tags, but keep the escaped characters
     *
     * @param string $value
     * @return \MUtil_Html_Raw
     */
    public static function formatDescription($value)
    {
        return \MUtil_Html::raw(strip_tags($value));
    }

    /**
     * Set column usage to use for the browser.
     *
     * Must be an array of arrays containing the input for TableBridge->setMultisort()
     *
     * @return array or false
     */
    public function getBrowseColumns()
    {
        $br = \MUtil_Html::create('br');

        $output[10] = array('gsu_survey_name', $br, 'gsu_survey_description');
        $output[20] = array('gsu_surveyor_active', \MUtil_Html::raw($this->_(' [')), 'gso_source_name',
            \MUtil_Html::raw($this->_(']')), $br, 'gsu_status_show');
        $output[30] = array('gsu_active', $br, 'gsu_id_primary_group');
        $output[40] = array('gsu_insertable', $br, 'gsu_code');

        return $output;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return $this->_('Surveys');
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('survey', 'surveys', $count);
    }

}