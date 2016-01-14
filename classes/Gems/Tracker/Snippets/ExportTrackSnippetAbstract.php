<?php

/**
 * Copyright (c) 2015, Erasmus MC
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
 * DISCLAIMED. IN NO EVENT SHALL MAGNAFACTA BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Tracker\Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: ExportTrackSnippetAbstract.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

namespace Gems\Tracker\Snippets;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker\Snippets
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Jan 4, 2016 11:20:07 AM
 */
class ExportTrackSnippetAbstract extends \MUtil_Snippets_WizardFormSnippetAbstract
{
    /**
     *
     * @var \Gems_Task_TaskRunnerBatch
     */
    private $_batch;

    /**
     *
     * @var \Gems_User_User
     */
    protected $currentUser;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var \MUtil_Model_ModelAbstract
     */
    protected $exportModel;

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     * The name of the action to forward to after form completion
     *
     * @var string
     */
    protected $routeAction = 'show';

    /**
     * Variable to either keep or throw away the request data
     * not specified in the route.
     *
     * @var boolean True then the route is reset
     */
    public $resetRoute = true;

    /**
     *
     * @var \Gems_Tracker_Engine_TrackEngineInterface
     */
    protected $trackEngine;

    /**
     *
     * @var \Gems_Util
     */
    protected $util;

    /**
     *
     * @var \Zend_View
     */
    protected $view;

    /**
     * Add the elements from the model to the bridge for the current step
     *
     * @param \MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @param int $step The current step
     */
    protected function addStepElementsFor(\MUtil_Model_Bridge_FormBridgeInterface $bridge, \MUtil_Model_ModelAbstract $model, $step)
    {
        $this->displayHeader($bridge, sprintf(
                $this->_('%s track export. Step %d of %d.'),
                $this->trackEngine->getTrackName(),
                $step,
                $this->getStepCount()), 'h1');

        switch ($step) {
            case 2:
                $this->addStepExportCodes($bridge, $model);
                break;

            case 3:
                $this->addStepGenerateExportFile($bridge, $model);
                break;

            default:
                $this->addStepExportSettings($bridge, $model);
                break;

        }
    }

    /**
     * Add the elements from the model to the bridge for the current step
     *
     * @param \MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function addStepExportCodes(\MUtil_Model_Bridge_FormBridgeInterface $bridge, \MUtil_Model_ModelAbstract $model)
    {
        $this->displayHeader($bridge, $this->_('Set the survey export codes'), 'h3');

        $rounds      = $this->formData['rounds'];
        $surveyCodes = array();

        foreach ($rounds as $roundId) {
            $round = $this->trackEngine->getRound($roundId);
            $sid   = $round->getSurveyId();
            $name  = 'survey__' . $sid;

            $surveyCodes[$name] = $name;
            $model->set($name, 'validator', array('ValidateSurveyExportCode', true, array($sid, $this->db)));
        }
        $this->addItems($bridge, $surveyCodes);
    }

    /**
     * Add the elements from the model to the bridge for the current step
     *
     * @param \MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function addStepExportSettings(\MUtil_Model_Bridge_FormBridgeInterface $bridge, \MUtil_Model_ModelAbstract $model)
    {
        $this->displayHeader($bridge, $this->_('Select what to export'), 'h3');

        $this->addItems($bridge, 'orgs', 'fields', 'rounds');
    }

    /**
     * Add the elements from the model to the bridge for the current step
     *
     * @param \MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function addStepGenerateExportFile(\MUtil_Model_Bridge_FormBridgeInterface $bridge, \MUtil_Model_ModelAbstract $model)
    {
        // Things go really wrong (at the session level) if we run this code
        // while the finish button was pressed
        if (isset($this->formData[$this->finishButtonId]) && $this->formData[$this->finishButtonId]) {
            return;
        }
        $this->displayHeader($bridge, $this->_('Creating the export file'), 'h3');

        $this->nextDisabled = true;

        $batch = $this->getExportBatch();
        $form  = $bridge->getForm();

        $batch->setFormId($form->getId());
        $batch->autoStart = true;

        // \MUtil_Registry_Source::$verbose = true;
        if ($batch->run($this->request)) {
            exit;
        }

        $element = $form->createElement('html', $batch->getId());

        if ($batch->isFinished()) {
            $this->nextDisabled = $batch->getCounter('export_errors');
            $batch->autoStart   = false;

            // Keep the filename after $batch->getMessages(true) cleared the previous
            $filename = $batch->getSessionVariable('filename');
            $this->addMessage($batch->getMessages(true));
            $batch->setSessionVariable('filename', $filename);

            if ($this->nextDisabled) {
                $element->pInfo($this->_('Export errors occurred.'));
            } else {
                $p = $element->pInfo($this->_('Export file generated: '));

                $name = \MUtil_File::cleanupName($this->trackEngine->getTrackName()) . '.track.txt';

                $p->a(array('file' => 'go', $this->stepFieldName => 'download'), $name, array('type' => 'application/download'));
            }

        } else {
            $element->setValue($batch->getPanel($this->view, $batch->getProgressPercentage() . '%'));

        }
        $form->activateJQuery();
        $form->addElement($element);
    }

    /**
     * Overrule this function for any activities you want to take place
     * after the form has successfully been validated, but before any
     * buttons are processed.
     *
     * @param int $step The current step
     */
    protected function afterFormValidationFor($step)
    {
        if (2 == $step) {
            $model = $this->getModel();
            $saves = array();
            foreach ($model->getCol('surveyId') as $name => $sid) {
                if (isset($this->formData[$name]) && $this->formData[$name]) {
                    $saves[] = array('gsu_id_survey' => $sid, 'gsu_export_code' => $this->formData[$name]);
                }
            }

            $sModel = new \MUtil_Model_TableModel('gems__surveys');
            \Gems_Model::setChangeFieldsByPrefix($sModel, 'gus', $this->currentUser->getUserId());
            $sModel->saveAll($saves);

            $count = $sModel->getChanged();

            if ($count == 0) {
                $this->addMessage($this->_('No export code changed'));
            } else {
                $this->addMessage(sprintf(
                        $this->plural('%d export code changed', '%d export codes changed', $count),
                        $count
                        ));
            }
        }
    }

    /**
     * Hook that allows actions when data was saved
     *
     * When not rerouted, the form will be populated afterwards
     *
     * @param int $changed The number of changed rows (0 or 1 usually, but can be more)
     */
    protected function afterSave($changed)
    {
        $this->addMessage($this->_('Track export finished'));
    }

    /**
     * Creates an empty form. Allows overruling in sub-classes.
     *
     * @param mixed $options
     * @return \Zend_Form
     */
    protected function createForm($options = null)
    {
        if (\MUtil_Bootstrap::enabled()) {
            if (!isset($options['class'])) {
                $options['class'] = 'form-horizontal';
            }

            if (!isset($options['role'])) {
                $options['role'] = 'form';
            }
        }
        return new \Gems_Form($options);
    }

    /**
     * Creates the model
     *
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        if (! $this->exportModel instanceof \MUtil_Model_ModelAbstract) {
            $yesNo = $this->util->getTranslated()->getYesNo();

            $model = new \MUtil_Model_SessionModel('export_for_' . $this->request->getControllerName());

            $model->set('orgs', 'label', $this->_('Organization export'),
                    'default', 1,
                    'description', $this->_('Export the organzations for which the track is active'),
                    'multiOptions', $yesNo,
                    'required', true,
                    'elementClass', 'Checkbox');

            $model->set('fields', 'label', $this->_('Field export'));
            $fields = $this->trackEngine->getFieldNames();
            if ($fields) {
                $model->set('fields',
                        'default', array_keys($fields),
                        'description', $this->_('Check the fields to export'),
                        'elementClass', 'MultiCheckbox',
                        'multiOptions', $fields
                        );
            } else {
                $model->set('fields',
                        'elementClass', 'Exhibitor',
                        'value', $this->_('No fields to export')
                        );
            }

            $rounds = $this->trackEngine->getRoundDescriptions();
            $model->set('rounds', 'label', $this->_('Round export'));
            if ($rounds) {
                $defaultRounds = array();
                foreach ($rounds as $roundId => &$roundDescription) {
                    $round = $this->trackEngine->getRound($roundId);
                    if ($round && $round->isActive()) {
                        $defaultRounds[] = $roundId;
                    } else {
                        $roundDescription = sprintf($this->_('%s (inactive)'), $roundDescription);
                    }

                    $survey = $round->getSurvey();
                    if ($survey) {
                        $model->set('survey__' . $survey->getSurveyId(),
                                'label', $survey->getName(),
                                'default', $survey->getExportCode(),
                                'description', $this->_('A unique code indentifying this survey during track import'),
                                'maxlength', 64,
                                'required', true,
                                'size', 20,
                                'surveyId', $survey->getSurveyId()
                                );
                    }
                }
                $model->set('rounds',
                        'default', $defaultRounds,
                        'description', $this->_('Check the rounds to export'),
                        'elementClass', 'MultiCheckbox',
                        'multiOptions', $rounds
                        );
            } else {
                $model->set('rounds',
                        'elementClass', 'Exhibitor',
                        'value', $this->_('No rounds to export')
                        );
            }

            $this->exportModel = $model;
        }

        return $this->exportModel;
    }

    /**
     * Display a header
     *
     * @param \MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param mixed $header Header content
     * @param string $tagName
     */
    protected function displayHeader(\MUtil_Model_Bridge_FormBridgeInterface $bridge, $header, $tagName = 'h2')
    {
        static $count = 0;

        $count += 1;
        $element = $bridge->getForm()->createElement('html', 'step_header_' . $count);
        $element->$tagName($header);

        $bridge->addElement($element);
    }

    /**
     * Performs actual download
     *
     * @param \MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function downloadExportFile()
    {
        $this->view->layout()->disableLayout();
        \Zend_Controller_Action_HelperBroker::getExistingHelper('viewRenderer')->setNoRender(true);

        $filename     = $this->getExportBatch(false)->getSessionVariable('filename');
        $downloadName = \MUtil_File::cleanupName($this->trackEngine->getTrackName()) . '.track.txt';

        header("Content-Type: application/download");
        header("Content-Disposition: attachment; filename=\"$downloadName\"");
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: cache");                          // HTTP/1.0
        readfile($filename);
        exit();
    }

    /**
     *
     * @return \Gems_Task_TaskRunnerBatch
     */
    protected function getExportBatch($load = true)
    {
        if ($this->_batch) {
            return $this->_batch;
        }

        $this->_batch = $this->loader->getTaskRunnerBatch('track_export_' . $this->trackEngine->getTrackId());

        if ((! $load) || $this->_batch->isFinished()) {
            return $this->_batch;
        }

        if (! $this->_batch->isLoaded()) {
            $filename = \MUtil_File::createTemporaryIn(GEMS_ROOT_DIR . '/var/tmp/export/track');
            $trackId  = $this->trackEngine->getTrackId();
            $this->_batch->setSessionVariable('filename', $filename);

            $this->_batch->addTask(
                    'Tracker\\Export\\MainTrackExportTask',
                    $this->trackEngine->getTrackId(),
                    $this->formData['orgs']
                    );

            \MUtil_Echo::track($this->formData['fields']);
            foreach ($this->formData['fields'] as $fieldId) {
                $this->_batch->addTask(
                        'Tracker\\Export\\TrackFieldExportTask',
                        $trackId,
                        $fieldId
                        );
            }

            foreach ($this->formData['rounds'] as $roundId) {
                $this->_batch->addTask(
                        'Tracker\\Export\\TrackRoundExportTask',
                        $trackId,
                        $roundId
                        );
            }

        } else {
            $filename = $this->_batch->getSessionVariable('filename');
        }

        $this->_batch->setVariable('file', fopen($filename, 'a'));

        return $this->_batch;
    }

    /**
     * The number of steps in this form
     *
     * @return int
     */
    protected function getStepCount()
    {
        return 3;
    }

    /**
     * Hook that loads the form data from $_POST or the model
     *
     * Or from whatever other source you specify here.
     */
    protected function loadFormData()
    {
        $model = $this->getModel();

        if ($this->request->isPost()) {
            $this->formData = $model->loadPostData($this->request->getPost() + $this->formData, true);

        } elseif ('download' == $this->request->getParam($this->stepFieldName)) {
            $this->formData = $this->request->getParams();
            $this->downloadExportFile();

        } else {
            // Assume that if formData is set it is the correct formData
            if (! $this->formData)  {
                $this->formData = $model->loadNew();
            }
        }
        // \MUtil_Echo::track($this->formData);
    }

    /**
     * Set what to do when the form is 'finished'.
     *
     * @return \MUtil_Snippets_Standard_ModelImportSnippet
     */
    protected function setAfterSaveRoute()
    {
        $filename = $this->getExportBatch(false)->getSessionVariable('filename');
        if ($filename) {
            // Now is a good moment to remove the temporary file
            @unlink($filename);
        }

       // Default is just go to the index
        if ($this->routeAction && ($this->request->getActionName() !== $this->routeAction)) {
            $this->afterSaveRouteUrl = array(
                $this->request->getControllerKey() => $this->request->getControllerName(),
                $this->request->getActionKey()     => $this->routeAction,
                \MUtil_Model::REQUEST_ID           => $this->request->getParam(\MUtil_Model::REQUEST_ID),
                );
        }

        return $this;
    }
}
