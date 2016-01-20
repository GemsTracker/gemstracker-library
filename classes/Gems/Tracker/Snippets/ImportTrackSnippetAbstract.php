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
 * @version    $Id: ImportTrackSnippetAbstract.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

namespace Gems\Tracker\Snippets;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker\Snippets
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Jan 15, 2016 3:57:15 PM
 */
class ImportTrackSnippetAbstract extends \MUtil_Snippets_WizardFormSnippetAbstract
{
    /**
     *
     * @var \Zend_Session_Namespace
     */
    protected $_session;

    /**
     *
     * @var \Zend_Cache_Core
     */
    protected $cache;


    /**
     *
     * @var \Gems_User_User
     */
    protected $currentUser;

    /**
     *
     * @var \MUtil_Model_ModelAbstract
     */
    protected $importModel;

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var \Zend_View
     */
    public $view;

    /**
     * Add the settings from the transformed import data to the formData and the model
     *
     * @param \ArrayObject $import
     */
    public function addImportToModelData(\ArrayObject $import)
    {
        if (isset($import['formDefaults']) && $import['formDefaults']) {
            foreach ($import['formDefaults'] as $name => $default) {
                if (! (isset($this->formData[$name]) && $this->formData[$name])) {
                    $this->formData[$name] = $default;
                }
            }
        }

        // \MUtil_Echo::track($this->formData);

        if (isset($import['modelSettings']) && $import['modelSettings']) {
            $model = $this->getModel();
            foreach ($import['modelSettings'] as $name => $settings) {
                // \MUtil_Echo::track($name, $settings);
                $model->set($name, $settings);
            }
        }
    }

    /**
     * Add the elements from the model to the bridge for file check step
     *
     * @param \MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function addStepChangeTrack(\MUtil_Model_Bridge_FormBridgeInterface $bridge, \MUtil_Model_ModelAbstract $model)
    {
        $this->displayHeader($bridge, $this->_('Change track information.'), 'h3');

        // Always add organization select, even when they were not exported
        $this->addItems($bridge, $model->getColNames('respondentData'));

        $import = $this->loadImportData();

        // \MUtil_Echo::track($this->formData);
        $all = $this->loader->getUtil()->getTrackData()->getAllSurveys();
        $available = array('' => $this->_('(skip rounds)')) + $all;
        // \MUtil_Echo::track($all);

        $form = $bridge->getForm();

        $surveyHeader = $form->createElement('Html', 'sheader1');
        $surveyHeader->h2($this->_('Survey export code links'));
        $form->addElement($surveyHeader);

        $surveySubHeader = $form->createElement('Html', 'sheader2');
        $surveySubHeader->strong($this->_('Linked survey name'));
        $surveySubHeader->setLabel($this->_('Import survey name'))
                ->setDescription(sprintf($this->_('[%s]'), $this->_('export code')))
                ->setRequired(true);
        $form->addElement($surveySubHeader);

        $this->addItems($bridge, $model->getColNames('isSurvey'));

        // \MUtil_Echo::track($this->_session->uploadFileName, $import->getArrayCopy());
    }

    /**
     * Add the elements from the model to the bridge for file check step
     *
     * @param \MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function addStepCreateTrack(\MUtil_Model_Bridge_FormBridgeInterface $bridge, \MUtil_Model_ModelAbstract $model)
    {
        // Things go really wrong (at the session level) if we run this code
        // while the finish button was pressed
        if ($this->isFinishedClicked()) {
            return;
        }

        $this->nextDisabled = true;

        $this->displayHeader($bridge, $this->_('Creating the track.'), 'h3');

        $batch = $this->getImportCreateBatch();
        $form  = $bridge->getForm();

        $batch->setFormId($form->getId());
        $batch->autoStart = true;

        // \MUtil_Registry_Source::$verbose = true;
        if ($batch->run($this->request)) {
            exit;
        }

        $element = $form->createElement('html', $batch->getId());

        if ($batch->isFinished()) {
            $this->nextDisabled = $batch->getCounter('create_errors');
            $batch->autoStart   = false;

            // Keep the filename after $batch->getMessages(true) cleared the previous
            $this->addMessage($batch->getMessages(true));
            if ($this->nextDisabled) {
                $element->pInfo($this->_('Create errors occurred!'));
            } else {
                $element->h2($this->_('Track created successfully OK!'));
                $element->pInfo($this->_('Click the "Finish" button to see the track.'));
            }
        } else {
            $element->setValue($batch->getPanel($this->view, $batch->getProgressPercentage() . '%'));
        }

        $form->activateJQuery();
        $form->addElement($element);
    }

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
                $this->_('New track import. Step %d of %d.'),
                $step,
                $this->getStepCount()), 'h1');

        switch ($step) {
            case 2:
                $this->addStepFileCheck($bridge, $model);
                break;

            case 3:
                $this->addStepChangeTrack($bridge, $model);
                break;

            case 4:
                $this->addStepCreateTrack($bridge, $model);
                break;

            default:
                $this->addStepFileImport($bridge, $model);
                break;

        }
    }

    /**
     * Add the elements from the model to the bridge for file check step
     *
     * @param \MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function addStepFileCheck(\MUtil_Model_Bridge_FormBridgeInterface $bridge, \MUtil_Model_ModelAbstract $model)
    {
        if ($this->onStartStep() && $this->isNextClicked()) {
            return;
        }
        $this->nextDisabled = true;

        $this->displayHeader($bridge, $this->_('Checking the content of the file.'), 'h3');

        $batch = $this->getImportCheckBatch();
        $form  = $bridge->getForm();

        $batch->setFormId($form->getId());
        $batch->autoStart = true;

        // \MUtil_Registry_Source::$verbose = true;
        if ($batch->run($this->request)) {
            exit;
        }

        $element = $form->createElement('html', $batch->getId());

        if ($batch->isFinished()) {
            $this->nextDisabled = $batch->getCounter('import_errors');
            $batch->autoStart   = false;

            // Keep the filename after $batch->getMessages(true) cleared the previous
            $this->addMessage($batch->getMessages(true));
            if ($this->nextDisabled) {
                $element->pInfo($this->_('Import errors occurred!'));
            } else {
                $element->h2($this->_('Import checks OK!'));
                $element->pInfo($this->_('Click the "Next" button to continue.'));
            }
        } else {
            $element->setValue($batch->getPanel($this->view, $batch->getProgressPercentage() . '%'));
        }

        $form->activateJQuery();
        $form->addElement($element);
    }

    /**
     * Add the elements from the model to the bridge for file upload step
     *
     * @param \MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function addStepFileImport(\MUtil_Model_Bridge_FormBridgeInterface $bridge, \MUtil_Model_ModelAbstract $model)
    {
        // Reset the data
        $this->_session->importData = null;

        $this->displayHeader($bridge, $this->_('Upload a track definition file.'), 'h3');

        $this->addItems($bridge, 'trackFile');

        $element = $bridge->getForm()->getElement('trackFile');

        if ($element instanceof \Zend_Form_Element_File) {
            if (file_exists($this->_session->localfile)) {
                unlink($this->_session->localfile);
            }
            // Now add the rename filter, the localfile is known only once after loadFormData() has run
            $element->addFilter(new \Zend_Filter_File_Rename(array(
                'target'    => $this->_session->localfile,
                'overwrite' => true
                )));

            $uploadFileName = $element->getFileName();

            // Download the data oon post with filename
            if ($this->request->isPost() && $uploadFileName && $element->isValid(null)) {
                // \MUtil_Echo::track($element->getFileName(), $element->getFileSize());
                if (!$element->receive()) {
                    throw new \MUtil_Model_ModelException(sprintf(
                        $this->_("Error retrieving file '%s'."),
                        $element->getFileName()
                        ));
                }

                $this->_session->importData = null;
                $this->_session->uploadFileName = basename($uploadFileName);
                // \MUtil_Echo::track($this->_session->uploadFileName);
            }
        }
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
        if (3 == $step) {
            $import = $this->loadImportData();
            $model  = $this->getModel();
            $saves  = array();

            foreach ($model->getCol('exportCode') as $name => $exportCode) {
                if (isset($this->formData[$name]) && $this->formData[$name]) {
                    $saves[] = array('gsu_id_survey' => $this->formData[$name], 'gsu_export_code' => $exportCode);

                    $import['surveyCodes'][$exportCode] = $this->formData[$name];
                }
            }
            if ($saves) {
                $sModel = new \MUtil_Model_TableModel('gems__surveys');
                \Gems_Model::setChangeFieldsByPrefix($sModel, 'gus', $this->currentUser->getUserId());
                $sModel->saveAll($saves);

                $count = $sModel->getChanged();

                if ($count == 0) {
                    $this->addMessage($this->_('No export code changed'));
                } else {
                    $this->cache->clean(\Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, array('surveys'));
                    $this->addMessage(sprintf(
                            $this->plural('%d export code changed', '%d export codes changed', $count),
                            $count
                            ));
                }
            }
        }
    }

    /**
     * Creates the model
     *
     * @return \MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        if (! $this->importModel instanceof \MUtil_Model_ModelAbstract) {

            $model = new \MUtil_Model_SessionModel('import_for_' . $this->request->getControllerName());

            $model->set('trackFile', 'label', $this->_('A .track.txt file'),
                    'count',        1,
                    'elementClass', 'File',
                    'extension',    'txt',
                    'description',  $this->_('Import an exported track using a file with the extension ".track.txt".'),
                    'required',     true
                    );

            $model->set('import_id');

            $trackModel = $this->loader->getTracker()->getTrackModel();
            $trackModel->applyFormatting(true, true);
            $model->set('gtr_track_name', $trackModel->get('gtr_track_name') + array('respondentData' => true));
            $model->set('gtr_organizations', $trackModel->get('gtr_organizations') + array('respondentData' => true));

            $this->importModel = $model;
        }

        return $this->importModel;
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
     *
     * @return \Gems_Task_TaskRunnerBatch
     */
    protected function getImportCheckBatch()
    {
        $batch  = $this->loader->getTaskRunnerBatch('track_import_check_' . $this->formData['import_id']);
        $import = $this->loadImportData();

        $batch->setVariable('import', $import);

        if ($batch->isFinished()) {
            return $batch;
        }

        if (! $batch->isLoaded()) {
            $batch->addTask(
                    'Tracker\\Import\\CheckTrackImportTask',
                    $import['track']
                    );

            foreach ($import['organizations'] as $lineNr => $organizationData) {
                $batch->addTask(
                        'Tracker\\Import\\CheckTrackOrganizationImportTask',
                        $lineNr,
                        $organizationData
                        );
            }

            foreach ($import['fields'] as $lineNr => $fieldData) {
                $batch->addTask(
                        'Tracker\\Import\\CheckTrackFieldImportTask',
                        $lineNr,
                        $fieldData
                        );
            }

            foreach ($import['surveys'] as $lineNr => $surveyData) {
                $batch->addTask(
                        'Tracker\\Import\\CheckTrackSurveyImportTask',
                        $lineNr,
                        $surveyData
                        );
            }

            foreach ($import['rounds'] as $lineNr => $roundData) {
                $batch->addTask(
                        'Tracker\\Import\\CheckTrackRoundImportTask',
                        $lineNr,
                        $roundData
                        );
            }

            $batch->addTask(
                    'Tracker\\Import\\CheckTrackImportErrorsTask',
                    $import['errors']
                    );
        }

        return $batch;
    }

    /**
     *
     * @return \Gems_Task_TaskRunnerBatch
     */
    protected function getImportCreateBatch()
    {
        $batch  = $this->loader->getTaskRunnerBatch('track_import_create_' . $this->formData['import_id']);
        $import = $this->loadImportData();

        $batch->setVariable('import', $import);

        if ($batch->isFinished()) {
            return $batch;
        }

        if (! $batch->isLoaded()) {
            $batch->addTask(
                    'Tracker\\Import\\CreateTrackImportTask',
                    $this->formData
                    );

            /*
            foreach ($import['fields'] as $lineNr => $fieldData) {
                $batch->addTask(
                        'Tracker\\Import\\CheckTrackFieldImportTask',
                        $lineNr,
                        $fieldData
                        );
            }

            foreach ($import['surveys'] as $lineNr => $surveyData) {
                $batch->addTask(
                        'Tracker\\Import\\CheckTrackSurveyImportTask',
                        $lineNr,
                        $surveyData
                        );
            }

            foreach ($import['rounds'] as $lineNr => $roundData) {
                $batch->addTask(
                        'Tracker\\Import\\CheckTrackRoundImportTask',
                        $lineNr,
                        $roundData
                        );
            }

            $batch->addTask(
                    'Tracker\\Import\\CheckTrackImportErrorsTask',
                    $import['errors']
                    );
            // */
        }

        return $batch;
    }

    /**
     * Returns an array with the allowed section names as keys and
     * as a value true when the field list is reset for every item
     * or false for the standard case where the first line contains
     * all the fields.
     *
     * @return array sectionName => resetFields
     */
    public function getImportSections()
    {
        return array(
            'track'  => false,
            'organizations' => false,
            'fields' => true,
            'surveys' => false,
            'rounds' => false,
            );
    }

    /**
     * The number of steps in this form
     *
     * @return int
     */
    protected function getStepCount()
    {
        return 4;
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

        } else {
            // Assume that if formData is set it is the correct formData
            if (! $this->formData)  {
                $this->formData = $model->loadNew();
            }
        }

        if (! (isset($this->formData['import_id']) && $this->formData['import_id'])) {
            $this->formData['import_id'] = mt_rand(10000,99999) . time();
        }
        $this->_session = new \Zend_Session_Namespace(__CLASS__ . '-' . $this->formData['import_id']);

        if (! (isset($this->_session->localfile) && $this->_session->localfile)) {
            $importLoader = $this->loader->getImportLoader();

            $this->_session->localfile = \MUtil_File::createTemporaryIn(
                    $importLoader->getTempDirectory(),
                    $this->request->getControllerName() . '_'
                    );
        }

        // \MUtil_Echo::track($this->formData);
    }

    /**
     * Load the import data in an array fo the type:
     *
     * \ArrayObject(array(
     *      'track' => array(linenr => array),
     *      'organizations' => array(linenr => array),
     *      'fields' => array(linenr => array),
     *      'surveys' => array(linenr => array),
     *      'rounds' => array(linenr => array),
     *      'errors' => array(linenr => string),
     *      ))
     *
     * Stored in session
     *
     * @return \ArrayObject
     */
    protected function loadImportData()
    {
        if (isset($this->_session->importData) && ($this->_session->importData instanceof \ArrayObject)) {
            // No need to run this after initial load, but we need to
            // run this every time afterwards.
            $this->addImportToModelData($this->_session->importData);
            return $this->_session->importData;
        }

        // Array object to avoid passing by reference
        $this->_session->importData = new \ArrayObject(array(
            'track' => array(),
            'organizations' => array(),
            'fields' => array(),
            'surveys' => array(),
            'rounds' => array(),
            'errors' => array(),
            ));

        $file = $this->_session->localfile;

        if (! file_exists($file)) {
            $this->_session->importData['errors'][] = sprintf(
                    $this->_('The import file "%s" seems to be missing.'),
                    $this->_session->uploadFileName
                    );
            return $this->_session->importData;
        }

        $content = file_get_contents($file);

        if (! $content) {
            $this->_session->importData['errors'][] = sprintf(
                    $this->_('The import file "%s" is empty.'),
                    $this->_session->uploadFileName
                    );
            return $this->_session->importData;
        }

        $fieldsCount = 0;
        $fieldsNames = false;
        $fieldsReset = false;
        $key         = false;
        $lineNr      = 0;
        $sections    = $this->getImportSections();

        foreach (explode("\r\n", $content) as $line) {
            $lineNr++;
            if ($line) {
                if (strpos($line, "\t") === false) {
                    $key         = strtolower(trim($line));
                    $fieldsNames = false;
                    $fieldsReset = false;

                    if (isset($sections[$key])) {
                        $fieldsReset = $sections[$key];
                    } else {
                        $this->_session->importData['errors'][] = sprintf(
                                $this->_('Unknown data type identifier "%s" found at line %.'),
                                trim($line),
                                $lineNr
                                );
                        $key = false;
                    }

                } else {
                    $raw = explode("\t", $line);

                    if ($fieldsNames) {
                        if (count($raw) === $fieldsCount) {
                            $data = array_combine($fieldsNames, $raw);
                            $this->_session->importData[$key][$lineNr] = $data;
                        } else {
                            $this->_session->importData['errors'][] = sprintf(
                                    $this->_('Incorrect number of fields at line %d. Found %d while %d expected.'),
                                    $lineNr,
                                    count($raw),
                                    $fieldsCount
                                    );
                        }
                        if ($fieldsReset) {
                            $fieldsNames = false;
                        }
                    } else {
                        $fieldsNames = $raw;
                        $fieldsCount = count($fieldsNames);
                    }
                }
            }
        }
        return $this->_session->importData;
    }

    /**
     * Set what to do when the form is 'finished'.
     *
     * @return \MUtil_Snippets_Standard_ModelImportSnippet
     * /
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
    } // */

    /**
     * Performs the validation.
     *
     * @return boolean True if validation was OK and data should be saved.
     * /
    protected function validateForm()
    {
        if (2 == $this->currentStep) {
            return true;
        }
        // Note we use an MUtil_Form
        return $this->_form->isValid($this->formData, $this->disableValidatorTranslation);
    } // */
}
