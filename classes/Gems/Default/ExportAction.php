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
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Standard controller for export of survey data
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Default_ExportAction extends \Gems_Controller_Action
{
    /**
     * Defines the value used for 'no round description'
     *
     * It this value collides with a used round description, change it to something else
     */
    const NoRound = '-1';

    /**
     *
     * @var \Zend_Session_Namespace
     */
    protected $_session;

    /**
     *
     * @var \Gems_AccessLog
     */
    public $accesslog;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;

    /**
     * @var \Gems_Export
     */
    public $export;

    /**
     *
     * @var \Zend_Locale
     */
    public $locale;

    /**
     *
     * @var \Gems_Project_ProjectSettings
     */
    public $project;

    protected function _addResponseDatabaseForm($form, &$data, &$elements)
    {
        // A little hack to get the form to align nice... at least for my layout. Do we actually use the small max-with that is currently set?
        $this->view->HeadStyle()->appendStyle('.tab-displaygroup input, .tab-displaygroup select { max-width: 39em; }');

        if (isset($data['tid']) && (!empty($data['tid']))) {
            // If we have a responsedatabase and a track id, try something cool ;-)
            $responseDb = $this->project->getResponseDatabase();
            if ($this->db === $responseDb) {
                // We are in the same database, now put that to use by allowing to filter respondents based on an answer in any survey
                $empty      = $this->util->getTranslated()->getEmptyDropdownArray();
                $allSurveys = $empty + $this->util->getDbLookup()->getSurveysForExport();

                $element = new \Zend_Form_Element_Select('filter_sid');
                $element->setLabel($this->_('Survey'))
                        ->setMultiOptions($allSurveys);

                $groupElements = array($element);

                if (isset($data['filter_sid']) && !empty($data['filter_sid'])) {
                    $filterSurvey    = $this->loader->getTracker()->getSurvey($data['filter_sid']);
                    $filterQuestions = $empty + $filterSurvey->getQuestionList($this->locale->getLanguage());

                    $element = new \Zend_Form_Element_Select('filter_answer');
                    $element->setLabel($this->_('Question'))
                            ->setMultiOptions($filterQuestions);
                    $groupElements[] = $element;
                }

                if (isset($filterSurvey) && isset($data['filter_answer']) && !empty($data['filter_answer'])) {
                    $questionInfo = $filterSurvey->getQuestionInformation($this->locale->getLanguage());

                    if (array_key_exists($data['filter_answer'], $questionInfo)) {
                        $questionInfo = $questionInfo[$data['filter_answer']];
                    } else {
                        $questionInfo = array();
                    }

                    if (array_key_exists('answers', $questionInfo) && is_array($questionInfo['answers']) && count($questionInfo['answers']) > 1) {
                        $element = new \Zend_Form_Element_Multiselect('filter_value');
                        $element->setMultiOptions($empty + $questionInfo['answers']);
                        $element->setAttrib('size', count($questionInfo['answers']) + 1);
                    } else {
                        $element = new \Zend_Form_Element_Text('filter_value');
                    }
                    $element->setLabel($this->_('Value'));
                    $groupElements[] = $element;
                }

                $form->addDisplayGroup($groupElements, 'filter', array('showLabels'  => true, 'Description' => $this->_('Filter')));
                array_shift($elements);
            }
        }
    }

    /**
     * Convert the submitted form-data to a filter to be used for retrieving the data to export
     *
     * Now only handles the organization ID and consent codes, but can be extended to
     * include track info or perform some checks
     *
     * @param array $data
     * @return array
     */
    protected function _getFilter($data)
    {
        $filter = array();
        if (isset($data['ids'])) {
            $idStrings = $data['ids'];

            $idArray = preg_split('/[\s,;]+/', $idStrings, -1, PREG_SPLIT_NO_EMPTY);

            if ($idArray) {
                // Make sure output is OK
                // $idArray = array_map(array($this->db, 'quote'), $idArray);

                $filter['respondentid'] = $idArray;
            }
        }

        if ($this->project->hasResponseDatabase()) {
            $this->_getResponseDatabaseFilter($data, $filter);
        }

        if (isset($data['tid'])) {
            $select = $this->db->select();
            $select->from('gems__respondent2track', array('gr2t_id_respondent_track'))
                    ->where('gr2t_id_track = ?', $data['tid']);

            if ($trackArray = $this->db->fetchCol($select)) {
                $filter['resptrackid'] = $trackArray;
            }
        }

        if (isset($data['oid'])) {
            $filter['organizationid'] = $data['oid'];
        } else {
            //Invalid id so when nothing selected... we get nothing
            // $filter['organizationid'] = '-1';
        }

        // Consent codes
        $filter['consentcode'] = array_diff(
                (array) $this->util->getConsentTypes(),
                (array) $this->util->getConsentRejected()
                );

        if (isset($data['rounds']) && !empty($data['rounds'])) {
            $select = $this->loader->getTracker()->getTokenSelect(array('gto_id_token'));

            // Only get positive receptioncodes
            $select->andReceptionCodes(array())
                   ->onlySucces();

            // Apply track filter
            if (isset($data['tid']) && !empty($data['tid'])) {
                $select->forWhere('gto_id_track = ?', (int) $data['tid']);
            }

            // Apply survey filter
            if (isset($data['sid']) && !empty($data['sid'])) {
                $select->forSurveyId((int) $data['sid']);
            }

            // Apply organization filter
            if (isset($data['oid'])) {
                $select->forWhere('gto_id_organization in (?)', $data['oid']);
            }

            // Apply round description filter
            if ($data['rounds'] == self::NoRound) {
                $select->forWhere('gto_round_description IS NULL OR gto_round_description = ""');
            } else {
                $select->forWhere('gto_round_description = ?', $data['rounds']);
            }

            $tokens = array();
            $result = $select->getSelect()->query();
            while ($row = $result->fetch(\Zend_Db::FETCH_NUM)) {
                $tokens[] = $row[0];
            }

            if (empty($tokens)) {
                // Add invalid filter
                $filter['organizationid'] = -1;
            }

            $filter['token'] = $tokens;
        }

        // \Gems_Tracker::$verbose = true;
        return $filter;
    }

    protected function _getResponseDatabaseFilter($data, &$filter)
    {
        if (isset($data['filter_answer']) &&
                (!empty($data['filter_answer'])) &&
                isset($data['filter_value']) &&
                $data['filter_value'] !== '') {

            $select = $this->db->select()
                    ->from('gemsdata__responses', array(''))
                    ->join('gems__tokens', 'gto_id_token = gdr_id_token', array(''))
                    ->where('gdr_answer_id = ?', $data['filter_answer']);

            if (is_array($data['filter_value'])) {
                $select->where('gdr_response IN (?)', $data['filter_value']);
            } else {
                $select->where('gdr_response = ?', $data['filter_value']);
            }

            $select->distinct()
                   ->columns('gto_id_respondent', 'gems__tokens');

            $result = $select->query()->fetchAll(\Zend_Db::FETCH_COLUMN);

            if (!empty($result)) {
                $filter['respondentid'] = $result;
            } else {
                $filter['respondentid'] = -1;
            }
        }
    }

    public function downloadAction()
    {
        $this->view->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $file = $this->_session->exportFile;
        foreach($file['headers'] as $header) {
            header($header);
        }
        while (ob_get_level()) {
            ob_end_clean();
        }
        readfile($file['file']);
        // Now clean up the file
        unlink($file['file']);

        exit;
    }

    /**
     * Retrieve the form
     *
     * @param array $data
     * @return \Gems_Form
     */
    public function getForm(&$data)
    {
        $dbLookup      = $this->util->getDbLookup();
        $translated    = $this->util->getTranslated();

        $empty         = $translated->getEmptyDropdownArray();
        $organizations = $this->loader->getCurrentUser()->getRespondentOrganizations();
        $noRound       = array(self::NoRound => $this->_('No round description'));
        $rounds        = $empty + $noRound + $dbLookup->getRoundsForExport(
                isset($data['tid']) ? $data['tid'] : null,
                isset($data['sid']) ? $data['sid'] : null
            );
        $tracks        = $empty + $this->util->getTrackData()->getSteppedTracks();
        $surveys       = $empty + $dbLookup->getSurveysForExport(isset($data['tid']) ? $data['tid'] : null);
        $types         = $this->export->getExportClasses();
        $yesNo         = $translated->getYesNo();

        //Create the basic form
        if (\MUtil_Bootstrap::enabled()) {
            $form = new \Gems_Form();
        } else {
            $form = new \Gems_Form_TableForm();
        }

        $form->getDecorator('AutoFocus')->setSelectall(false);

        //Start adding elements
        $element = $form->createElement('textarea', 'ids');
        $element->setLabel($this->_('Respondent id\'s'))
                ->setAttrib('cols', 60)
                ->setAttrib('rows', 4)
                ->setDescription($this->_('Not respondent nr, but respondent id as exported here.'));
        $elements[] = $element;

        $element = $form->createElement('select', 'tid');
        $element->setLabel($this->_('Tracks'))
            ->setMultiOptions($tracks);
        $elements[] = $element;

        if (isset($data['tid']) && $data['tid']) {
            $element = $form->createElement('radio', 'tid_fields');
            $element->setLabel($this->_('Export fields'))
                ->setMultiOptions($yesNo);
            $elements[] = $element;

            if (!array_key_exists('tid_fields', $data)) {
                $data['tid_fields'] = 1;
            }
        }

        $element = $form->createElement('select', 'sid');
        $element->setLabel($this->_('Survey'))
            ->setMultiOptions($surveys);
        $elements[] = $element;

        $element = $form->createElement('select', 'rounds');
        $element->setLabel($this->_('Round description'))
            ->setMultiOptions($rounds);
        $elements[] = $element;

        if ($this->project->hasResponseDatabase()) {
            $this->_addResponseDatabaseForm($form, $data, $elements);
        }

        //Add a field to the form showing the record count. If this is too slow for large recordsets
        //then remove it or make it more efficient
        unset($data['records']);
        if (!empty($data['sid'])) {
            $survey   = $this->loader->getTracker()->getSurvey(intval($data['sid']));
            $filter   = $this->_getFilter($data);
            //$answers  = $survey->getRawTokenAnswerRows($filter);
            //$recordCount = count($answers);
            $recordCount = $survey->getRawTokenAnswerRowsCount($filter);

            $element = $form->createElement('exhibitor', 'records');
            $element->setValue(sprintf($this->_('%s records found.'), $recordCount));
            $elements[] = $element;
        }

        $element = $form->createElement('multiCheckbox', 'oid');
        $element->setLabel($this->_('Organization'))
                ->setMultiOptions($organizations);
        $elements[] = $element;

        if (MUtil_Bootstrap::enabled()) {
            $element = new \MUtil_Bootstrap_Form_Element_ToggleCheckboxes('toggleOrg', array('selector'=>'input[name^=oid]'));
        } else {
            $element = new \Gems_JQuery_Form_Element_ToggleCheckboxes('toggleOrg', array('selector'=>'input[name^=oid]'));
        }

        $element->setLabel($this->_('Toggle'));
        $elements[] = $element;

        $element = $form->createElement('select', 'type');
        $element->setLabel($this->_('Export to'))
                ->setMultiOptions($types);
        $elements[] = $element;

        //Add all elements to the form
        $form->addElements($elements);
        unset($elements);

        //Now make a change for the selected export type
        if (isset($data['type'])) {
            $exportClass = $this->export->getExport($data['type']);
            $formFields  = $exportClass->getFormElements($form, $data);
            $exportName  = $exportClass->getName();

            //Now add a hidden field so we know that when this is present in the $data
            //we don't need to set the defaults
            $formFields[] = new \Zend_Form_Element_Hidden($exportName);
            foreach ($formFields as $formField) {
                $formField->setBelongsTo($exportName);
                $form->addElement($formField);
            }

            if (!isset($data[$exportName])) {
                $data[$exportName] = $exportClass->getDefaults();
            }
        }

        //Finally create a submit button and add to the form
        $element = $form->createElement('submit', 'export');
        $element->setLabel('Export');
        $form->addElement($element);

        return $form;
    }

    public function getTopic($count = 1)
    {
        return $this->_('Data');
    }

    public function getTopicTitle()
    {
        return $this->_('Export survey answers');
    }

    /**
     * Take care of exporting the data
     *
     * @param array $data
     */
    public function handleExport($data)
    {
        if (isset($data['type']) && !empty($data['sid'])) {
            //Do the logging
            $message = \Zend_Json::encode($data);
            $this->accesslog->logChange($this->getRequest());

            //And delegate the export to the right class
            $exportClass = $this->export->getExport($data['type']);

            if ($exportClass instanceof \Gems_Export_ExportBatchInterface) {
                // Clear possible existing batch
                $batch = $this->loader->getTaskRunnerBatch('export_data');
                $batch->reset();
                // Have a batch handle the export
                $this->_session->exportParams = $data;
                $this->_reroute(array('action'=>'handle-export'));

            } else {
                // If not possible / available, handle direct
                $language    = $this->locale->getLanguage();
                $survey      = $this->loader->getTracker()->getSurvey($data['sid']);
                $filter      = $this->_getFilter($data);
                $answers     = $survey->getRawTokenAnswerRows($filter);
                $answerModel = $survey->getAnswerModel($language);

                //Now add the organization id => name mapping
                $answerModel->set('organizationid', 'multiOptions', $this->loader->getCurrentUser()->getAllowedOrganizations());

                if (count($answers) === 0) {
                    $answers[0] = array('' => sprintf($this->_('No %s found.'), $this->getTopic(0)));
                }

                $exportClass->handleExport($data, $survey, $answers, $answerModel, $language);
            }
        }
    }

    public function handleExportAction()
    {
        $this->initHtml();
        $batch = $this->loader->getTaskRunnerBatch('export_data');
        $batch->minimalStepDurationMs = 2000;
        if (!$batch->count()) {
            $data     = $this->_session->exportParams;
            $filter   = $this->_getFilter($data);
            $language = $this->locale->getLanguage();

            $batch->addTask('Export_ExportCommand', $data['type'], 'handleExportBatch', $filter, $language, $data);
            $batch->autoStart = true;
        }

        $title = $this->_('Export');

        if ($batch->run($this->getRequest())) {
            exit;
        } else {
            $controller = $this;
            $controller->html->h3($title);

            if ($batch->isFinished()) {
                // $controller->addMessage($batch->getMessages());

                $file = $batch->getSessionVariable('file');
                if ((!empty($file)) && isset($file['file']) && file_exists($file['file'])) {
                    // Forward to download action
                    $this->_session->exportFile = $file;
                    $this->_reroute(array('action'=>'download'));
                }
            } else {
                if ($batch->count()) {
                    $controller->html->append($batch->getPanel($controller->view, $batch->getProgressPercentage() . '%'));
                } else {
                    $controller->html->pInfo($controller->_('Nothing to do.'));
                }
                $controller->html->pInfo()->a(
                        \MUtil_Html_UrlArrayAttribute::rerouteUrl($this->getRequest(), array('action'=>'index')),
                        array('class'=>'actionlink'),
                        $this->_('Back')
                        );
            }
        }
    }

    public function indexAction()
    {
        $this->initHtml();

        $data = isset($this->_session->exportParams) ? $this->_session->exportParams : array();
        $form = $this->processForm(null, $data);
        $this->_session->exportParams = array_filter($form->getValues());

        if ((! $this->getRequest()->isPost()) || $form->getElement('export')->isChecked()) {
            if ($form->getElement('export')->isChecked()) {
                $this->handleExport($form->getValues());
            }
            $this->html->h3($this->getTopicTitle());
            $div = $this->html->div(array('id' => 'mainform'));
            $div[] = $form;

        } else {
            // Hacked around to get a self-refreshing form, quite hardcoded but fine for now
            //
            // We do not need to return the layout, just the form
            $this->disableLayout();

            // $this->html->append($form);

            $this->html->raw($form->render($this->view));

            //Now add all onload actions to make the form still work
            $actions = $this->view->jQuery()->getOnLoadActions();
            $script  = $this->html->script(array('type' => "text/javascript"));
            foreach ($actions as $action) {
                $script->raw($action);
            }
            $this->html->raw($this->view->inlineScript());
            // \MUtil_Echo::track(htmlentities($script->render($this->view)));
            // \MUtil_Echo::track(htmlentities($this->view->inlineScript()));
            $this->html->raw(\MUtil_Echo::out());
        }
    }

    /**
     * Initialize translate and html objects
     *
     * Called from {@link __construct()} as final step of object instantiation.
     *
     * @return void
     */
    public function init()
    {
        parent::init();

        $this->export = $this->loader->getExport();

        //Add this controller to the export so it can render view when needed
        $this->export->controller = $this;

        // $this->_session = GemsEscort::getInstance()->session;
        $this->_session = new \Zend_Session_Namespace(__CLASS__);
    }

    /**
     * Handle the form
     *
     * @param type $saveLabel
     * @param type $data
     * @return type
     */
    public function processForm($saveLabel = null, $data = null)
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            $data = $request->getPost() + (array) $data;
        } else {
            //Set the defaults for the form here
            $data = $data + $this->export->getDefaults();
        }

        $form = $this->getForm($data);

        //Make the form 'autosubmit' so it can refresh
        $form->setAttrib('id', 'autosubmit');
        $form->setAutoSubmit(\MUtil_Html::attrib('href', array('action' => 'index', 'RouteReset' => true)), 'mainform');

        if ($data) {
            $form->populate($data);
        }
        return $form;
    }
}