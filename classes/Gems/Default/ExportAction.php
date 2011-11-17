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
 * @version    $Id: DatabaseAction.php 28 2011-09-16 06:24:15Z mennodekker $
 */

/**
 * Standard controller for database creation and maintenance.
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Default_ExportAction extends Gems_Controller_Action
{
    /**
     * @var Gems_Export
     */
    public $export;

    /**
     *
     * @var Zend_Locale
     */
    public $locale;

    public function __construct(Zend_Controller_Request_Abstract $request, Zend_Controller_Response_Abstract $response, array $invokeArgs = array())
    {
        parent::__construct($request, $response, $invokeArgs);
        $this->export = $this->loader->getExport();

        //Add this controller to the export so it can render view when needed
        $this->export->controller = $this;
    }

    public function getTopic($count = 1)
    {
        return $this->_('Data');
    }

    public function getTopicTitle()
    {
        return $this->_('Export data');
    }

    public function indexAction()
    {
        $this->initHtml();
        //Hacked around to get a self-refreshing form, quite hardcoded but fine for now
        if ($form = $this->processForm()) {
            if (!$this->getRequest()->isPost() || $form->getElement('export')->isChecked()) {
                if ($form->getElement('export')->isChecked()) {
                    $data = $form->getValues();
                    $this->handleExport($data);
                }
                $this->html->h3($this->getTopicTitle());
                $div = $this->html->div(array('id' => 'mainform'));
                $div[] = $form;
            } else {
                Zend_Layout::resetMvcInstance();
                $this->html->raw($form->render($this->view));

                //Now add all onload actions to make the form still work
                $actions = $this->view->jQuery()->getOnLoadActions();
                $this->html->raw('<script type="text/javascript">');
                foreach ($actions as $action) {
                    $this->html->raw($action);
                }
                $this->html->raw('</script>');
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
            $data = $this->export->getDefaults();
        }

        $form = $this->getForm($data);

        //Make the form 'autosubmit' so it can refresh
        $form->setAttrib('id', 'autosubmit');
        $form->setAutoSubmit(MUtil_Html::attrib('href', array('action' => 'index', MUtil_Model::TEXT_FILTER => null, 'RouteReset' => true)), 'mainform');

        if ($data) {
            $form->populate($data);
        }
        return $form;
    }

    /**
     * Retrieve the form
     *
     * @param array $data
     * @return Gems_Form
     */
    public function getForm(&$data)
    {
        //Read some data from tables, initialize defaults...
        $surveys       = $this->db->fetchPairs('SELECT gsu_id_survey, gsu_survey_name FROM gems__surveys WHERE gsu_active = 1 ORDER BY gsu_survey_name');
        $organizations = $this->loader->getCurrentUser()->getAllowedOrganizations();
        $types         = $this->export->getExportClasses();

        //Create the basic form
        $form = new Gems_Form_TableForm();

        //Start adding elements
        $element = new Zend_Form_Element_Select('sid');
        $element->setLabel($this->_('Survey'))
                ->setMultiOptions($surveys);
        $elements[] = $element;

        //Add a field to the form showing the record count. If this is too slow for large recordsets
        //then remove it or make it more efficient
        unset($data['records']);
        if (isset($data['sid'])) {
            $survey   = $this->loader->getTracker()->getSurvey(intval($data['sid']));
            $filter   = $this->_getFilter($data);
            $answers  = $survey->getRawTokenAnswerRows($filter);
        } else {
            $answers = array();
        }
        $element = new MUtil_Form_Element_Exhibitor('records');
        $element->setValue(sprintf($this->_('%s records found.'), count($answers)));
        $elements[] = $element;

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

    /**
     * Take care of exporting the data
     *
     * @param array $data
     */
    public function handleExport($data)
    {
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

        if (isset($data['type'])) {
            //Do the logging
            $message = Zend_Json::encode($data);
            Gems_AccessLog::getLog()->log('export', $this->getRequest(), $message, null, true);

            //And delegate the export to the right class
            $exportClass = $this->export->getExport($data['type']);
            $exportClass->handleExport($data, $survey, $answers, $answerModel, $language);
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
    public function _getFilter($data) {
        $filter = array();
        if (isset($data['oid'])) {
            $filter['organizationid'] = $data['oid'];
        } else {
            //Invalid id so when nothing selected... we get nothing
            //$filter['organizationid'] = '-1';
        }
        $filter['consentcode'] = array_diff((array) $this->util->getConsentTypes(), (array) $this->util->getConsentRejected());

        return $filter;
    }
}