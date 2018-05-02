<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker\Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: ImportTrackSnippetAbstract.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

namespace Gems\Tracker\Snippets;

use Gems\Tracker\Field\FieldInterface;
use Gems\Tracker\Round;

use MUtil\Validate\NotEqualExcept;

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
     * Deactivate this current round
     */
    const ROUND_DEACTIVATE = -1;

    /**
     * Leave the current round as is
     */
    const ROUND_LEAVE      = -2;

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
     * @var \Gems_Tracker_Engine_TrackEngineInterface
     */
    protected $trackEngine = false;

    /**
     *
     * @var \Gems_Util
     */
    protected $util;

    /**
     *
     * @var \Gems_View
     */
    public $view;

    /**
     * Add the settings from the transformed import data to the formData and the model
     *
     * @param \ArrayObject $import
     * @param \MUtil_Model_ModelAbstract $model
     */
    public function addExistingRoundsToModel(\ArrayObject $import, \MUtil_Model_ModelAbstract $model)
    {
        $currentRounds = $this->trackEngine->getRounds();

        if (! $currentRounds) {
            return;
        }

        $importRounds    = array();
        $newImportRounds = array();
        $tracker         = $this->loader->getTracker();

        foreach ($import['rounds'] as $lineNr => $roundData) {
            if (isset($roundData['survey_export_code'], $import['surveyCodes'][$roundData['survey_export_code']])) {
                $roundData['gro_id_survey'] = $import['surveyCodes'][$roundData['survey_export_code']];
                $round = $tracker->createTrackClass('Round', $roundData);

                $importRounds[$round->getRoundOrder()] = $round->getFullDescription();
                $newImportRounds[$round->getRoundOrder()] = sprintf(
                        $this->_('Set round to round %s'),
                        $round->getFullDescription()
                        );

                $import['roundOrderToLine'][$round->getRoundOrder()] = $lineNr;
            }
        }

        // Filter for rounds not in current track
        foreach ($currentRounds as $roundId => $round) {
            if ($round instanceof Round) {
                $order = $round->getRoundOrder();

                if (isset($newImportRounds[$order])) {
                    unset($newImportRounds[$order]);
                }
            }
        }

        $except     = array(self::ROUND_LEAVE, self::ROUND_DEACTIVATE);
        $notEqualTo = array();  // Make sure no round is imported twice
        foreach ($currentRounds as $roundId => $round) {
            if ($round instanceof Round) {
                $name  = "round_$roundId";
                $order = $round->getRoundOrder();

                $model->set($name,
                        'existingRound', true,
                        'required', true,
                        'roundId', $roundId
                        );
                if (isset($importRounds[$order])) {
                    if ($round->getFullDescription() == $importRounds[$order]) {
                        $options = array(
                            self::ROUND_LEAVE => $this->_('Leave current round'),
                            $order => $this->_('Replace with import round'),
                            );
                    } else {
                        $options = array(
                            self::ROUND_LEAVE => $this->_('Leave current round'),
                            $order => sprintf(
                                    $this->_('Replace with import round %s'),
                                    $importRounds[$order]
                                    ),
                            );
                    }
                    $model->set($name,
                            'label', sprintf(
                                    $this->_('Matching round %s'),
                                    $round->getFullDescription()
                                    ),
                            'elementClass', 'Radio',
                            'multiOptions', $options
                            );
                    $value = $order;
                } else {
                    $model->set($name,
                            'label', sprintf(
                                    $this->_('Round not in import: %s'),
                                    $round->getFullDescription()
                                    ),
                            'elementClass', 'Select',
                            'multiOptions', array(
                                self::ROUND_LEAVE => sprintf($this->_('Leave current round %d unchanged'), $order),
                                self::ROUND_DEACTIVATE => sprintf($this->_('Deactivate current round %d'), $order),
                                ) + $newImportRounds,
                            'size', 3 + count($newImportRounds)
                            );
                    $value = null;

                    if ($notEqualTo) {
                        $notEqualVal = new NotEqualExcept($notEqualTo, $except);
                        $model->set($name, 'validators[notequal]', $notEqualVal);
                    }
                    $notEqualTo[] = $name;
                }
                if (! array_key_exists($name, $this->formData)) {
                    $this->formData[$name] = $value;
                }
            }
        }
    }

    /**
     * Add the settings from the transformed import data to the formData and the model
     *
     * @param \ArrayObject $import
     */
    public function addImportToModelData(\ArrayObject $import)
    {
        // formDefaults are set in the Gems\Task\Tracker\Import tasks
        if (isset($import['formDefaults']) && $import['formDefaults']) {
            foreach ($import['formDefaults'] as $name => $default) {
                if (! (isset($this->formData[$name]) && $this->formData[$name])) {
                    $this->formData[$name] = $default;
                }
            }
        }

        // \MUtil_Echo::track($this->formData);

        // modelSettings are set in the Gems\Task\Tracker\Import tasks
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

        // Load the import data form settings
        $this->loadImportData();

        // Always add organization select, even when they were not exported
        $this->addItems($bridge, $model->getColNames('respondentData'));

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
                $element->pInfo($this->_('Errors occurred during import!'));
            } else {
                $element->h2($this->_('Track created successfully!'));
                $element->pInfo($this->_('Click the "Finish" button to see the track.'));
            }
        } else {
            $element->setValue($batch->getPanel($this->view, $batch->getProgressPercentage() . '%'));
        }

        $form->activateJQuery();
        $form->addElement($element);

        // \MUtil_Echo::track($this->loadImportData()->getArrayCopy());
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
        $this->displayHeader($bridge, $this->getFormTitle($step), 'h2');

        switch ($step) {
            case 2:
                $this->addStepFileCheck($bridge, $model);
                break;

            case 3:
                $this->addStepChangeTrack($bridge, $model);
                break;

            case 4:
                if ($this->trackEngine) {
                    $this->addStepRoundMatch($bridge, $model);
                } else {
                    $this->addStepCreateTrack($bridge, $model);
                }
                break;

            case 5:
                if ($this->trackEngine) {
                    $this->addStepMergeTrack($bridge, $model);
                    break;
                }
                // Intentional faal through

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

        $this->addItems($bridge, 'trackFile', 'gtr_id_track');

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
     * Add the elements from the model to the bridge for file check step
     *
     * @param \MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function addStepMergeTrack(\MUtil_Model_Bridge_FormBridgeInterface $bridge, \MUtil_Model_ModelAbstract $model)
    {
        // Things go really wrong (at the session level) if we run this code
        // while the finish button was pressed
        if ($this->isFinishedClicked()) {
            return;
        }

        $this->nextDisabled = true;

        $this->displayHeader($bridge, $this->_('Merging the tracks.'), 'h3');

        $batch = $this->getImportMergeBatch();
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
                $element->pInfo($this->_('Merge errors occurred!'));
            } else {
                $element->h2($this->_('Tracks mergeded successfully!'));
                $element->pInfo($this->_('Click the "Finish" button to see the merged track.'));
            }
        } else {
            $element->setValue($batch->getPanel($this->view, $batch->getProgressPercentage() . '%'));
        }

        $form->activateJQuery();
        $form->addElement($element);
    }

    /**
     * Add the elements from the model to the bridge for file check step
     *
     * @param \MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function addStepRoundMatch(\MUtil_Model_Bridge_FormBridgeInterface $bridge, \MUtil_Model_ModelAbstract $model)
    {
        $this->displayHeader($bridge, $this->_('Match the current track rounds to import rounds.'), 'h3');

        // Load the import data form settings
        $import = $this->loadImportData();

        $this->addExistingRoundsToModel($import, $model);

        $rounds = $model->getColNames('existingRound');

        if ($rounds) {
            $this->addItems($bridge, $rounds);
        } else {
            $bridge->addHtml('existingRound')->pInfo($this->_('No rounds in current track.'));
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
        parent::afterFormValidationFor($step);

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

        if ($this->trackEngine && 4 == $step) {
            $import = $this->loadImportData();
            $model  = $this->getModel();
            $saves  = array();

            $import['deactivateRounds'] = array();

            foreach ($model->getCol('roundId') as $name => $roundId) {
                $round = $this->trackEngine->getRound($roundId);
                if (isset($this->formData[$name]) && $this->formData[$name] && $round instanceof Round) {
                    switch ($this->formData[$name]) {
                        case self::ROUND_DEACTIVATE:
                            $import['deactivateRounds'][$roundId] = $round->getFullDescription();
                            break;

                        case self::ROUND_LEAVE:
                            if (isset($import['roundOrderToLine'][$round->getRoundOrder()])) {
                                $lineNr = $import['roundOrderToLine'][$round->getRoundOrder()];
                                unset($import['rounds'][$lineNr]);
                            }
                            $import['roundOrders'][$round->getRoundOrder()] = $roundId;
                            break;

                        default:
                            if (isset($import['roundOrderToLine'][$this->formData[$name]])) {
                                $lineNr = $import['roundOrderToLine'][$this->formData[$name]];
                                $import['rounds'][$lineNr]['gro_id_round'] = $roundId;
                            }
                            $import['roundOrders'][$this->formData[$name]] = $roundId;
                            break;
                    }
                }
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
        if ($this->trackEngine) {
            $this->addMessage($this->_('Track merge finished'));
        } else {
            $this->addMessage($this->_('Track import finished'));
        }
        $this->cache->clean(\Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, array('tracks'));
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

            $model->set('importId');

            $options = array('' => $this->_('<<create a new track>>')) + $this->util->getTrackData()->getAllTracks();
            $model->set('gtr_id_track', 'label', $this->_('Merge with'),
                    'description', $this->_('Create a new track or choose a track to merge the import into.'),
                    'default', '',
                    'multiOptions', $options,
                    'size', min(12, count($options) + 1)
                    );

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
     * Creates from the model a \Zend_Form using createForm and adds elements
     * using addFormElements().
     *
     * @param int $step The current step
     * @return \Zend_Form
     */
    protected function getFormFor($step)
    {
        $baseform = $this->createForm();
        if ($this->trackEngine &&
                ($step == 4) &&
                (\MUtil_Bootstrap::enabled() !== true) &&
                ($baseform instanceof \MUtil_Form)) {
            $model = $this->getModel();
            $table = new \MUtil_Html_DivFormElement();
            $table->setAsFormLayout($baseform);

            $baseform->setAttrib('class', $this->class);

            $bridge = $model->getBridgeFor('form', $baseform);

            $this->_items = null;
            $this->initItems();

            $this->addFormElementsFor($bridge, $model, $step);

            return $baseform;
        } else {
            return parent::getFormFor($step);
        }
    }

    /**
     * Get the title at the top of the form
     *
     * @param int $step The current step
     * @return string
     */
    protected function getFormTitle($step)
    {
        if ($this->trackEngine) {
            return sprintf(
                    $this->_('Merge import into "%s" track. Step %d of %d.'),
                    $this->trackEngine->getTrackName(),
                    $step,
                    $this->getStepCount()
                    );
        }
        return sprintf(
                $this->_('New track import. Step %d of %d.'),
                $step,
                $this->getStepCount()
                );
    }

    /**
     *
     * @return \Gems_Task_TaskRunnerBatch
     */
    protected function getImportCheckBatch()
    {
        $batch  = $this->loader->getTaskRunnerBatch('track_import_check_' . $this->formData['importId']);
        $import = $this->loadImportData();

        $batch->setVariable('import', $import);

        if ($this->trackEngine) {
            $batch->setVariable('trackEngine', $this->trackEngine);
        }

        if ($batch->isFinished()) {
            return $batch;
        }

        if (! $batch->isLoaded()) {
            $batch->addTask(
                    'Tracker\\Import\\CheckVersionImportTask',
                    $import['version']
                    );

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

            if (isset($import['errors']) && $import['errors']) {
                $batch->addTask(
                        'Tracker\\Import\\CheckTrackImportErrorsTask',
                        $import['errors']
                        );
            }
        }

        return $batch;
    }

    /**
     *
     * @return \Gems_Task_TaskRunnerBatch
     */
    protected function getImportCreateBatch()
    {
        $batch  = $this->loader->getTaskRunnerBatch('track_import_create_' . $this->formData['importId']);
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

            foreach ($import['fields'] as $lineNr => $fieldData) {
                $batch->addTask(
                        'Tracker\\Import\\CreateTrackFieldImportTask',
                        $lineNr,
                        $fieldData
                        );
            }

            foreach ($import['rounds'] as $lineNr => $roundData) {
                $batch->addTask(
                        'Tracker\\Import\\CreateTrackRoundImportTask',
                        $lineNr,
                        $roundData
                        );
            }

            $batch->addTask(
                    'AddTask',
                    'Tracker\\Import\\FinishTrackImport'
                    );
        }

        return $batch;
    }

    /**
     *
     * @return \Gems_Task_TaskRunnerBatch
     */
    protected function getImportMergeBatch()
    {
        $batch  = $this->loader->getTaskRunnerBatch('track_import_create_' . $this->formData['importId']);
        $import = $this->loadImportData();

        $batch->setVariable('import', $import);
        $batch->setVariable('trackEngine', $this->trackEngine);

        if ($batch->isFinished()) {
            return $batch;
        }

        if (! $batch->isLoaded()) {
            $batch->addTask(
                    'Tracker\\Merge\\MergeTrackImportTask',
                    $this->formData
                    );

            $fieldDef = $this->trackEngine->getFieldsDefinition();
            foreach ($import['fields'] as $lineNr => &$fieldData) {
                $field = $fieldDef->getFieldByOrder($fieldData['gtf_id_order']);
                if ($field instanceof FieldInterface) {
                    $fieldData['gtf_id_field'] = $field->getFieldId();
                }

                $batch->addTask(
                        'Tracker\\Import\\CreateTrackFieldImportTask',
                        $lineNr,
                        $fieldData
                        );
            }

            foreach ($import['rounds'] as $lineNr => $roundData) {
                $batch->addTask(
                        'Tracker\\Import\\CreateTrackRoundImportTask',
                        $lineNr,
                        $roundData
                        );
            }

            if (isset($import['deactivateRounds'])) {
                foreach ($import['deactivateRounds'] as $roundId => $roundDescription) {
                    $batch->addTask(
                            'Tracker\\Merge\\DeactivateTrackFieldTask',
                            $roundId,
                            $roundDescription
                            );
                }
            }

            $batch->addTask(
                    'AddTask',
                    'Tracker\\Import\\FinishTrackImport'
                    );
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
            'version'       => false,
            'track'         => false,
            'organizations' => false,
            'fields'        => true,
            'surveys'       => false,
            'rounds'        => false,
            );
    }

    /**
     * The number of steps in this form
     *
     * @return int
     */
    protected function getStepCount()
    {
        if ($this->trackEngine) {
            return 5;
        }
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

        if (isset($this->formData['gtr_id_track']) && $this->formData['gtr_id_track']) {
            $this->trackEngine = $this->loader->getTracker()->getTrackEngine($this->formData['gtr_id_track']);
        }
        if (! (isset($this->formData['importId']) && $this->formData['importId'])) {
            $this->formData['importId'] = mt_rand(10000,99999) . time();
        }
        $this->_session = new \Zend_Session_Namespace(__CLASS__ . '-' . $this->formData['importId']);

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

        $sections = $this->getImportSections();

        // Array object to have automatic passing by reference
        $this->_session->importData = new \ArrayObject(array_fill_keys(
                array_keys($sections) + array('errors'),
                array()
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
                                $this->_('Unknown data type identifier "%s" found at line %s.'),
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
     * @return \Gems\Tracker\Snippets\ImportMergeSnippetAbstract
     */
    protected function setAfterSaveRoute()
    {
        if ($this->_session->localfile && file_exists($this->_session->localfile)) {
            // Now is a good moment to remove the temporary file
            @unlink($this->_session->localfile);
        }

        $import = $this->loadImportData();

        if (isset($import['trackId']) && $import['trackId']) {
            $trackId = $import['trackId'];
            $this->routeAction = 'show';
        } else {
            $trackId = null;
        }


        // Default is just go to the index
        if ($this->routeAction && ($this->request->getActionName() !== $this->routeAction)) {
            $this->afterSaveRouteUrl = array(
                $this->request->getControllerKey() => $this->request->getControllerName(),
                $this->request->getActionKey()     => $this->routeAction,
                \MUtil_Model::REQUEST_ID           => $trackId,
                );
        }

        return $this;
    }

    /**
     * Performs the validation.
     *
     * @return boolean True if validation was OK and data should be saved.
     */
    protected function validateForm()
    {
        if (2 == $this->currentStep) {
            return true;
        }
        // Note we use an MUtil_Form
        return $this->_form->isValid($this->formData, $this->disableValidatorTranslation);
    }
}