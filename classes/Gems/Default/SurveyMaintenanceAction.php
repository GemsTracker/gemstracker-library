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
class Gems_Default_SurveyMaintenanceAction extends Gems_Controller_BrowseEditAction
{
    public $autoFilter = true;

    public $menuEditIncludeLevel = 100;

    public $menuShowIncludeLevel = 100;

    public $sortKey = array('gsu_survey_name' => SORT_ASC);

    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Adds a button column to the model, if such a button exists in the model.
     *
     * @param MUtil_Model_TableBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     * @rturn void
     */
    protected function addBrowseTableColumns(MUtil_Model_TableBridge $bridge, MUtil_Model_ModelAbstract $model)
    {
        parent::addBrowseTableColumns($bridge, $model);

        // Add pdf button if allowed
        if ($menuItem = $this->findAllowedMenuItem('pdf')) {
            $bridge->addItemLink(MUtil_Lazy::iif($bridge->gsu_has_pdf, $menuItem->toActionLinkLower($this->getRequest(), $bridge)));
        }
    }

    /**
     * Adds elements from the model to the bridge that creates the form.
     *
     * Overrule this function to add different elements to the browse table, without
     * having to recode the core table building code.
     *
     * @param MUtil_Model_FormBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     * @param array $data The data that will later be loaded into the form
     * @param optional boolean $new Form should be for a new element
     * @return void|array When an array of new values is return, these are used to update the $data array in the calling function
     */
    protected function addFormElements(MUtil_Model_FormBridge $bridge, MUtil_Model_ModelAbstract $model, array $data, $new = false)
    {
        // MUtil_Echo::track($data);

        // Prepare variables
        $currentId    = $data['gsu_id_survey'];

        $survey       = $this->loader->getTracker()->getSurvey($currentId);
        $standAlone   = $this->escort instanceof Gems_Project_Tracks_StandAloneSurveysInterface;
        $surveyFields = $this->util->getTranslated()->getEmptyDropdownArray() + $survey->getQuestionList($this->locale->getLanguage());
        $surveyNotOK  = $data['gsu_surveyor_active'] ? null : 'disabled';

        // Forced data changes
        if ($surveyNotOK) {
            $data['gsu_active'] = 0;
        }
        if (! isset($data['track_count'])) {
            $data['track_count'] = $this->getTrackCount($currentId);
        }

        $bridge->addHiddenMulti('gsu_id_survey', 'gsu_surveyor_id'); // Key fields
        $bridge->addHidden(     'gsu_survey_pdf');

        $bridge->addExhibitor(  'gsu_survey_name',           'size', 25);
        if (isset($data['gsu_survey_description']) && strlen(trim($data['gsu_survey_description']))) {
            $bridge->addExhibitor('gsu_survey_description',  'size', 60);
        } else {
            $bridge->addHidden('gsu_survey_description');
        }
        $bridge->addExhibitor(  'gsu_status_show');
        $bridge->addExhibitor(  'gsu_surveyor_active');

        $bridge->addCheckbox(   'gsu_active',                'disabled', $surveyNotOK)
                ->addValidator( new MUtil_Validate_Require($model->get('gsu_active', 'label'), 'gsu_id_primary_group', $model->get('gsu_id_primary_group', 'label')));

        $bridge->addSelect(     'gsu_id_primary_group',      'description', $this->_('If empty, survey will never show up!'));
        $bridge->addSelect(     'gsu_result_field',          'multiOptions', $surveyFields);
        $bridge->addText(       'gsu_duration');
        $bridge->addText(       'gsu_code');
        $bridge->addSelect(     'gsu_beforeanswering_event');
        $bridge->addSelect(     'gsu_completed_event');

        $bridge->addFile(       'new_pdf',                'label', $this->_('Upload new PDF'),
                'accept', 'application/pdf',
                'destination', $this->loader->getPdf()->getUploadDir('survey_pdfs'),
                'extension', 'pdf',
                'filename', $data['gsu_id_survey'],
                'required', false)
               ->addValidator(new MUtil_Validate_Pdf());

        $bridge->addExhibitor(  'track_count', 'label', $this->_('Usage'), 'value', $data['track_count']);

        if ($standAlone) {
            // Forced data changes
            if ($surveyNotOK) {
                $data['gsu_active'] = 0;
                $data['gro_active'] = 0;
                $data['gtr_active'] = 0;
            } else {
                // These are always active when the survey is active,
                // though they are only saved in the right circumstances of course. ;)
                $data['gro_active'] = $data['gsu_active'];
                $data['gtr_active'] = $data['gsu_active'];
            }

            $bridge->addHtml(       'stand_alone_surveys')->h4($this->_('Single Survey Assignment'));
            $bridge->addHidden(     'gsu_as_survey');                // Needed for FakeSubmitButton
            $bridge->addHiddenMulti('gro_active', 'gtr_active');     // Can change in this code
            $bridge->addHiddenMulti('gro_id_round', 'gtr_id_track'); // Key fields enable storing of results

            if (! $data['gsu_as_survey']) {
                if ($data['create_stand_alone']) {
                    $newValues = array(
                        // Only for insert
                        'gro_id_track'          => null,
                        'gro_id_order'          => 10,
                        'gro_id_survey'         => $currentId,
                        'gro_survey_name'       => $data['gsu_survey_name'],
                        'gro_round_description' => 'Stand-alone survey',
                        'gtr_track_name'        => $data['gsu_survey_name'],
                        'gtr_survey_rounds'     => 1,
                        'gtr_track_type'        => 'S',

                        // New normal values
                        'gtr_track_info'        => strip_tags($data['gsu_survey_description']),
                        'gtr_date_start'        => new Zend_Date(),
                        'gtr_date_until'        => null,
                        'gsu_as_survey'         => true,
                        );
                    $data = $newValues + $data;
                }
            }

            if ($data['gsu_as_survey'] || $data['create_stand_alone']) {
                // The magic: NOW we say we save the tables
                //
                // PS: order is important, this save gems__tracks first, as
                //     this may create the gtr_id_track needed in gems__rounds.
                $model->setTableSaveable('gems__tracks', 'gtr');
                $model->setTableSaveable('gems__rounds', 'gro');

                if ($data['create_stand_alone']) {
                    // These fields are needed only when the stand alone survey is being created.
                    $bridge->addHiddenMulti('gro_id_order', 'gro_id_track',
                            'gro_id_survey', 'gro_survey_name', 'gro_round_description',
                            'gtr_track_name', 'gtr_survey_rounds', 'gtr_track_type');
                }

                $bridge->addHidden('create_stand_alone');
                $bridge->addText(  'gtr_track_info', 'label', $this->_('Description'));
                $bridge->addDate(  'gtr_date_start', 'label', $this->_('Assignable since'));
                $bridge->addDate(  'gtr_date_until', 'label', $this->_('Assignable until'));
                // feature request #200
                $bridge->addMultiCheckbox('gtr_organizations', 'label', $this->_('Organizations'), 'multiOptions', $this->util->getDbLookup()->getOrganizations(), 'required', true);

            } else {
                $standAloneButton = new MUtil_Form_Element_FakeSubmit('create_stand_alone');
                $standAloneButton->setLabel($this->_('Create Single Survey'));
                $standAloneButton->setAttrib('disabled', $surveyNotOK);

                $bridge->addElement($standAloneButton);
                $bridge->addExhibitor('stand_alone_explanation', 'value', $this->_('At the moment this survey can only be assigned to respondents as part of an existing track.'));
            }
        }

        $this->setMenuParameter($data);

        return $data;
    }

    /**
     * @param array $data
     * @param bool  $isNew
     * @return array
     */
    public function afterFormLoad(array &$data, $isNew)
    {
        // feature request #200
        if (isset($data['gtr_organizations']) && (! is_array($data['gtr_organizations']))) {
            $data['gtr_organizations'] = explode('|', trim($data['gtr_organizations'], '|'));
        }
    }

    /**
     *
     * @param array $data The data that will be saved.
     * @param boolean $isNew
     * $param Zend_Form $form
     * @return array|null Returns null if save was already handled, the data otherwise.
     */
    public function beforeSave(array &$data, $isNew, Zend_Form $form = null)
    {
        // MUtil_Model::$verbose = true;
        if (isset($data['new_pdf'])) {
            // Make sure the record is saved in the database when a file was uploaded
            $this->getModel()->setAutoSave('gsu_changed', false);
            $data['gsu_changed'] = null;
        }

        // Set the value of the field in the database.
        $new_name = $data['gsu_id_survey'] . '.pdf';

        if (file_exists($form->new_pdf->getDestination() . DIRECTORY_SEPARATOR . $new_name)) {
            $data['gsu_survey_pdf'] = $new_name;
        } else {
            $data['gsu_survey_pdf'] = null;
        }

        $data['gtr_track_class'] = 'SingleSurveyEngine';

        // feature request #200
        if (isset($data['gtr_organizations']) && is_array($data['gtr_organizations'])) {
            $data['gtr_organizations'] = '|' . implode('|', $data['gtr_organizations']) . '|';
        }

        if ($data['gsu_active']==1 && empty($data['gsu_id_primary_group'])) {
            $this->addMessage($this->_('Survey should be assigned to a group before making it active.'));
            return false;
        }

        return true;
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
    }

    /**
     * Check the tokens for all surveys
     */
    public function checkAllAction()
    {
        $batch = $this->loader->getTracker()->recalculateTokens('surveyCheckAll', $this->loader->getCurrentUser()->getUserId());

        $title = $this->_('Checking survey results for all surveys.');
        $this->_helper->BatchRunner($batch, $title);
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
     * @return MUtil_Model_ModelAbstract
     */
    public function createModel($detailed, $action)
    {
        $standAlone = $this->escort instanceof Gems_Project_Tracks_StandAloneSurveysInterface;

        $yesNo = $this->util->getTranslated()->getYesNo();

        if ($standAlone) {
            // WHY EXPLANATION
            //
            // We have to LEFT JOIN
            //  - the INNER JOIN of gems__surveys and gems__sources
            // with
            //  - the INNER JOIN of gems__tracks and gems__rounds WHERE gtr_track_type = 'S'
            //
            // This would be possible in SQL using brackets in the join statement, but
            // Zend_Db_Select does not support this.
            //
            // However, by using a RIGHT JOIN we do not need the brackets.
            //
            // Hence the unexpected order of the tables in the JoinModel.
            $model = new Gems_Model_JoinModel('surveys', 'gems__tracks');
            $model->addTable('gems__rounds', array('gro_id_track' => 'gtr_id_track', 'gtr_track_type' => new Zend_Db_Expr("'S'")));
            $model->addRightTable('gems__surveys', array('gsu_id_survey' => 'gro_id_survey'), 'gus');
            $model->addTable('gems__sources', array('gsu_id_source'=>'gso_id_source'));
            $model->setKeysToTable('gems__surveys');

            $model->addColumn(
                    "CASE WHEN gtr_id_track IS NOT NULL THEN 1 ELSE 0 END",
                    'gsu_as_survey');

            if ('edit' === $action) {
                $model->addColumn(new Zend_Db_Expr('NULL'), 'create_stand_alone');
            }
        } else {
            $model = new Gems_Model_JoinModel('surveys', 'gems__surveys', 'gus');
            $model->addTable('gems__sources', array('gsu_id_source'=>'gso_id_source'));
        }

        $model->addColumn(
            "CASE WHEN gsu_survey_pdf IS NULL OR CHAR_LENGTH(gsu_survey_pdf) = 0 THEN 0 ELSE 1 END",
            'gsu_has_pdf');
        $model->addColumn(
            "COALESCE(gsu_status, '" . $this->_('OK') . "')",
            'gsu_status_show');

        $model->resetOrder();

        $model->set('gsu_survey_name',        'label', $this->_('Name'));
        $model->set('gsu_survey_description', 'label', $this->_('Description'), 'formatFunction', array(__CLASS__, 'formatDescription'));
        $model->set('gso_source_name',        'label', $this->_('Source'));
        $model->set('gsu_status_show',        'label', $this->_('Status in source'));

        if ($detailed) {
            $model->set('gsu_surveyor_active',    'label', $this->_('Active in source'));
            $model->set('gsu_active',             'label', sprintf($this->_('Active in %s'), GEMS_PROJECT_NAME_UC));
        } else {
            $model->set('gsu_active',             'label', $this->_('Active'));
        }
        $model->set('gsu_active',             'multiOptions', $yesNo);

        if ($standAlone) {
            $model->set('gsu_as_survey',      'label', $this->_('Single'), 'multiOptions', $yesNo);
        }

        $model->set('gsu_surveyor_active',    'multiOptions', $yesNo);
        $model->set('gsu_id_primary_group',   'label', $this->_('Group'), 'multiOptions', $this->util->getDbLookup()->getGroups());

        if ($detailed) {
            $events = $this->loader->getEvents();

            $model->set('gsu_result_field',          'label', $this->_('Result field'));
            $model->set('gsu_duration',              'label', $this->_('Duration description'), 'description', $this->_('Text to inform the respondent.'));
            
            $model->setIfExists('gsu_code', 'label', $this->_('Code name'), 'size', 10, 'description', $this->_('Only for programmers.'));
            
            $model->set('gsu_beforeanswering_event', 'label', $this->_('Before answering'), 'multiOptions', $events->listSurveyBeforeAnsweringEvents());
            $model->set('gsu_completed_event',       'label', $this->_('After completion'), 'multiOptions', $events->listSurveyCompletionEvents());
        }

        $model->setCreate(false);

        return $model;
    }

    public static function formatDescription($value)
    {
        return MUtil_Html::raw(strip_tags($value));
    }

    public function getTrackCount($currentId)
    {
        $singleTrack = ($this->escort instanceof Gems_Project_Tracks_SingleTrackInterface) ? $this->escort->getTrackId() : null;

        $select = new Zend_Db_Select($this->db);
        $select->from('gems__rounds', array('useCnt' => 'COUNT(*)', 'trackCnt' => 'COUNT(DISTINCT gro_id_track)'));
        if ($singleTrack) {
            $select->where("gro_id_track = ?", $singleTrack);
        } else {
            $select->joinLeft('gems__tracks', 'gtr_id_track = gro_id_track', array())
                    ->group('gems__tracks.gtr_id_track')
                    ->where("gtr_track_type = 'T'");
        }
        $select->where('gro_id_survey = ?', $currentId);

        if ($counts = $select->query()->fetchObject()) {
            if ($singleTrack) {
                return sprintf($this->_('%d times in track.'), $counts->useCnt);
            } else {
                return sprintf($this->_('%d times in %d track(s).'), $counts->useCnt, $counts->trackCnt);
            }
        } else {
            if ($singleTrack) {
                return $this->_('Not used in track.');
            } else {
                return $this->_('Not used in tracks.');
            }
        }
    }

    public function getTopic($count = 1)
    {
        return $this->plural('survey', 'surveys', $count);
    }

    public function getTopicTitle()
    {
        return $this->_('Surveys');
    }

    public function pdfAction()
    {
        // Make sure nothing else is output
        $this->initRawOutput();

        // Output the PDF
        $this->loader->getPdf()->echoPdfBySurveyId($this->_getParam(MUtil_Model::REQUEST_ID));
    }

    public function setMenuParameter($data)
    {
        $source = $this->menu->getParameterSource();
        $source->offsetSet('gsu_has_pdf', $data['gsu_survey_pdf'] ? 1 : 0);
        $source->offsetSet(MUtil_Model::REQUEST_ID, $data['gsu_id_survey']);

        return $this;
    }

     /**
     * Shows a table displaying a single record from the model
     *
     * Uses: $this->getModel()
     *       $this->getShowTable();
     */
    public function showAction()
    {
        $this->html->h3(sprintf($this->_('Show %s'), $this->getTopic()));

        $model    = $this->getModel();
        $data     = $model->load();

        $this->setMenuParameter(reset($data));

        $repeater = MUtil_Lazy::repeat($data);
        $table    = $this->getShowTable();
        $table->setOnEmpty(sprintf($this->_('Unknown %s.'), $this->getTopic(1)));
        $table->setRepeater($repeater);
        $table->tfrow($this->createMenuLinks($this->menuShowIncludeLevel), array('class' => 'centerAlign'));

        $this->html[] = $table;

        if ($this->escort->hasPrivilege('pr.project.questions')) {
            $this->addSnippet('SurveyQuestionsSnippet', 'surveyId', $this->_getIdParam());
        }
    }
}