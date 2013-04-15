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
class Gems_Default_ExportAction extends Gems_Controller_Action
{
    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    //public $db;

    /**
     * @var Gems_Export
     */
    public $export;

    /**
     *
     * @var Zend_Locale
     */
    public $locale;
    
    /**
     *
     * @var Gems_Util_RequestCache
     */
    public $requestCache;
    
    /**
     *
     * @var Zend_Session_Namespace
     */
    protected $_session;

    public function __construct(Zend_Controller_Request_Abstract $request, Zend_Controller_Response_Abstract $response, array $invokeArgs = array())
    {
        parent::__construct($request, $response, $invokeArgs);
        $this->export = $this->loader->getExport();

        //Add this controller to the export so it can render view when needed
        $this->export->controller = $this;
        
        $this->_session = GemsEscort::getInstance()->session;
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
            //$filter['organizationid'] = '-1';
        }
        $filter['consentcode'] = array_diff((array) $this->util->getConsentTypes(), (array) $this->util->getConsentRejected());
        
        // Gems_Tracker::$verbose = true;
        return $filter;
    }
    
    /**
     * Modify request to hold a cache
     * 
     * @return array
     */
    public function getCachedRequestData()
    {
        if (! $this->requestCache) {
            $this->requestCache = $this->util->getRequestCache($this->getRequest()->getActionName(), false);
            $this->requestCache->setMenu($this->menu);
            $this->requestCache->setRequest($this->getRequest());

            // Button text should not be stored.
            $this->requestCache->removeParams('export', 'action');
        }

        $data = $this->requestCache->getProgramParams();

        // Clean up empty values
        //
        // We do this here because empty values can be valid filters that overrule the default
        foreach ($data as $key => $value) {
            if ((is_array($value) && empty($value)) || (is_string($value) && 0 === strlen($value))) {
                unset($data[$key]);
            }
        }

        // Do not set, we only want to have the data as a default
        //$this->getRequest()->setParams($data);

        return $data;
    }

    /**
     * Retrieve the form
     *
     * @param array $data
     * @return Gems_Form
     */
    public function getForm(&$data)
    {
        $empty         = $this->util->getTranslated()->getEmptyDropdownArray();
        $tracks        = $empty + $this->util->getTrackData()->getSteppedTracks();
        $surveys       = $empty + $this->util->getDbLookup()->getSurveysForExport(isset($data['tid']) ? $data['tid'] : null);
        $organizations = $this->loader->getCurrentUser()->getRespondentOrganizations();
        $types         = $this->export->getExportClasses();

        //Create the basic form
        $form = new Gems_Form_TableForm();
        $form->getDecorator('AutoFocus')->setSelectall(false);

        //Start adding elements
        $element = new Zend_Form_Element_Textarea('ids');
        $element->setLabel($this->_('Respondent id\'s'))
                ->setAttrib('cols', 60)
                ->setAttrib('rows', 4)
                ->setDescription($this->_('Not respondent nr, but respondent id as exported here.'));
        $elements[] = $element;

        $element = new Zend_Form_Element_Select('tid');
        $element->setLabel($this->_('Tracks'))
            ->setMultiOptions($tracks);
        $elements[] = $element;

        $element = new Zend_Form_Element_Select('sid');
        $element->setLabel($this->_('Survey'))
            ->setMultiOptions($surveys);
        $elements[] = $element;

        //Add a field to the form showing the record count. If this is too slow for large recordsets
        //then remove it or make it more efficient
        unset($data['records']);
        if (!empty($data['sid'])) {
            $survey   = $this->loader->getTracker()->getSurvey(intval($data['sid']));
            $filter   = $this->_getFilter($data);
            //$answers  = $survey->getRawTokenAnswerRows($filter);
            //$recordCount = count($answers);
            $recordCount = $survey->getRawTokenAnswerRowsCount($filter);

            $element = new MUtil_Form_Element_Exhibitor('records');
            $element->setValue(sprintf($this->_('%s records found.'), $recordCount));
            $elements[] = $element;
        }

        $element = new Zend_Form_Element_MultiCheckbox('oid');
        $element->setLabel($this->_('Organization'))
                ->setMultiOptions($organizations);
        $elements[] = $element;

        $element = new Gems_JQuery_Form_Element_ToggleCheckboxes('toggleOrg', array('selector'=>'input[name^=oid]'));
        $element->setLabel('Toggle');
        $elements[] = $element;

        $element = new Zend_Form_Element_Select('type');
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
            $formFields[] = new Zend_Form_Element_Hidden($exportName);
            foreach ($formFields as $formField) {
                $formField->setBelongsTo($exportName);
                $form->addElement($formField);
            }

            if (!isset($data[$exportName])) {
                $data[$exportName] = $exportClass->getDefaults();
            }
        }

        //Finally create a submit button and add to the form
        $element = new Zend_Form_Element_Submit('export');
        $element->setLabel('Export')
                ->setAttrib('class', 'button');
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
        if (isset($data['type'])) {
            //Do the logging
            $message = Zend_Json::encode($data);
            Gems_AccessLog::getLog()->log('export', $this->getRequest(), $message, null, true);
            
            //And delegate the export to the right class
            $exportClass = $this->export->getExport($data['type']);
            
            if ($exportClass instanceof Gems_Export_ExportBatchInterface) {
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
            
            $exportClass = $this->export->getExport($data['type']);
            $exportClass->handleExportBatch($batch, $filter, $language, $data);            
            $batch->autoStart = true;
        }
        
        $title = $this->_('Export');
        // Not using batchrunner since we need something else
        if ($batch->run($this->getRequest())) {
            exit;
        } else {
            $controller = $this;          
            $controller->html->h3($title);

            if ($batch->isFinished()) {
                $messages = $batch->getMessages();
                if (array_key_exists('file', $messages)) {
                    $files = $messages['file'];
                    unset($messages['file']);
                    unset($messages['export-progress']);
                }
                
                $controller->addMessage($messages);

                if (!empty($files) && array_key_exists('file', $files) && file_exists(GEMS_ROOT_DIR . '/var/tmp/' . $files['file'])) {
                    // Forward to download action
                    $this->_session->exportFile = $files;
                    $this->_reroute(array('action'=>'download'));
                }
            } else {
                if ($batch->count()) {
                    $controller->html->append($batch->getPanel($controller->view, $batch->getProgressPercentage() . '%'));                   
                } else {
                    $controller->html->pInfo($controller->_('Nothing to do.'));
                }
                $controller->html->pInfo(MUtil_Html_AElement::a(MUtil_Html_UrlArrayAttribute::rerouteUrl($this->getRequest(), array('action'=>'index')), array('class'=>'actionlink'), $this->_('Back')));
            }
        }
    }
    
    public function downloadAction()
    {
        $this->view->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
        $files = $this->_session->exportFile;
        foreach($files['headers'] as $header) {
            header($header);
        }
        while (ob_get_level()) {
            ob_end_clean();
        }
        readfile(GEMS_ROOT_DIR . '/var/tmp/' . $files['file']);
        // Now clean up the file
        unlink(GEMS_ROOT_DIR . '/var/tmp/' . $files['file']);
        exit;
    }
           

    public function indexAction()
    {
        $this->initHtml();
        
        $data = $this->getCachedRequestData();

        //Hacked around to get a self-refreshing form, quite hardcoded but fine for now
        if ($form = $this->processForm(null, $data)) {
            if (!$this->getRequest()->isPost() || $form->getElement('export')->isChecked()) {
                if ($form->getElement('export')->isChecked()) {
                    $data = $form->getValues();
                    $this->handleExport($data);
                }
                $this->html->h3($this->getTopicTitle());
                $div = $this->html->div(array('id' => 'mainform'));
                $div[] = $form;
            } else {
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
                // MUtil_Echo::track(htmlentities($script->render($this->view)));
                // MUtil_Echo::track(htmlentities($this->view->inlineScript()));
                $this->html->raw(MUtil_Echo::out());
            }
        }
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
        $form->setAutoSubmit(MUtil_Html::attrib('href', array('action' => 'index', 'RouteReset' => true)), 'mainform');

        if ($data) {
            $form->populate($data);
        }
        return $form;
    }
}