<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker\Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Snippets;

use Gems\Audit\AuditLog;
use Gems\Batch\BatchRunnerLoader;
use Gems\Legacy\CurrentUserRepository;
use Gems\Model;
use Gems\Model\MetaModelLoader;
use Gems\Repository\ImportRepository;
use Gems\Repository\TrackDataRepository;
use Gems\Task\TaskRunnerBatch;
use Gems\Task\TrackImportRunnerBatch;
use Gems\Tracker;
use Gems\Tracker\Field\FieldInterface;
use Gems\Tracker\Model\TrackModel;
use Gems\Tracker\Round;
use Gems\Util\Translated;
use Mezzio\Session\SessionInterface;
use MUtil\Validator\NotEqualExcept;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\File\File;
use Zalt\Loader\ProjectOverloader;
use Zalt\Message\MessengerInterface;
use Zalt\Model\Bridge\FormBridgeInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\Ra\SessionModel;
use Zalt\Snippets\Zend\ZendFormSnippetTrait;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker\Snippets
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Jan 15, 2016 3:57:15 PM
 */
class ImportTrackSnippetAbstract extends \Zalt\Snippets\WizardFormSnippetAbstract
{
    use ZendFormSnippetTrait { ZendFormSnippetTrait::beforeDisplay as zendBeforeDisplay; }

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
     * @var \Gems\User\User
     */
    protected $currentUser;

    protected SessionModel $importModel;

    protected ?ResponseInterface $response;

    protected array $sessionData;

    protected readonly string $sessionId;

    /**
     *
     * @var \Gems\Tracker\Engine\TrackEngineInterface
     */
    protected $trackEngine = false;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        CurrentUserRepository $currentUserRepository,
        protected readonly AuditLog $auditLog,
        protected readonly BatchRunnerLoader $batchRunnerLoader,
        protected readonly ImportRepository $importRepository,
        protected readonly MetaModelLoader $metaModelLoader,
        protected readonly ProjectOverloader $overloader,
        protected readonly ServerRequestInterface $request,
        protected readonly SessionInterface $session,
        protected readonly Tracker $tracker,
        protected readonly TrackDataRepository $trackDataRepository,
        protected readonly Translated $translatedUtil,
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger);

        $this->currentUser = $currentUserRepository->getCurrentUser();
        $this->sessionId   = get_class($this) . '_data';
        $this->sessionData = $this->session->get($this->sessionId, []);
    }

    /**
     * Add the settings from the transformed import data to the formData and the model
     *
     * @param \ArrayObject $import
     * @param DataReaderInterface $model
     */
    public function addExistingRoundsToModel(\ArrayObject $import, DataReaderInterface $model)
    {
        $currentRounds = $this->trackEngine->getRounds();

        if (! $currentRounds) {
            return;
        }

        $importRounds    = array();
        $newImportRounds = array();

        foreach ($import['rounds'] as $lineNr => $roundData) {
            if (isset($roundData['survey_export_code'], $import['surveyCodes'][$roundData['survey_export_code']])) {
                $roundData['gro_id_survey'] = $import['surveyCodes'][$roundData['survey_export_code']];
                $round = $this->tracker->createTrackClass('Round', $roundData);

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
        // formDefaults are set in the \Gems\Task\Tracker\Import tasks
        if (isset($import['formDefaults']) && $import['formDefaults']) {
            foreach ($import['formDefaults'] as $name => $default) {
                if (! (isset($this->formData[$name]) && $this->formData[$name])) {
                    $this->formData[$name] = $default;
                }
            }
        }

        // \MUtil\EchoOut\EchoOut::track($this->formData);

        // modelSettings are set in the \Gems\Task\Tracker\Import tasks
        if (isset($import['modelSettings']) && $import['modelSettings']) {
            $model = $this->getModel();
            foreach ($import['modelSettings'] as $name => $settings) {
                // \MUtil\EchoOut\EchoOut::track($name, $settings);
                $model->set($name, $settings);
            }
        }
    }

    protected function addStepChangeTrack(FormBridgeInterface $bridge, DataReaderInterface $model)
    {
        $this->displayHeader($bridge, $this->_('Change track information.'), 'h3');

        // Load the import data form settings
        $this->loadImportData();

        // Always add organization select, even when they were not exported
        $this->addItems($bridge, $model->getColNames('respondentData'));

        // \MUtil\EchoOut\EchoOut::track($this->formData);
        $all = $this->trackDataRepository->getAllSurveys();
        $available = array('' => $this->_('(skip rounds)')) + $all;
        // \MUtil\EchoOut\EchoOut::track($all);

        $form = $bridge->getForm();

        $surveyHeader = $form->createElement('Html', 'sheader1');
        $surveyHeader->h3($this->_('Survey export code links'));
        $form->addElement($surveyHeader);

        $surveySubHeader = $form->createElement('Html', 'sheader2');
        $surveySubHeader->strong($this->_('Linked survey name'));
        $surveySubHeader->setLabel($this->_('Import survey name'))
                ->setDescription(sprintf($this->_('[%s]'), $this->_('export code')))
                ->setRequired(true);
        $form->addElement($surveySubHeader);

        $this->addItems($bridge, $model->getColNames('isSurvey'));

        // \MUtil\EchoOut\EchoOut::track($this->_session->uploadFileName, $import->getArrayCopy());
    }

    protected function addStepCreateTrack(FormBridgeInterface $bridge, DataReaderInterface $model)
    {
        // Things go really wrong (at the session level) if we run this code
        // while the finish button was pressed
        if ($this->isFinishedClicked()) {
            return;
        }

        $this->nextDisabled = true;

        $this->displayHeader($bridge, $this->_('Creating the track.'), 'h3');

        $batch = $this->getImportCreateBatch();
        $batch->setFormStep($this->currentStep);
        $batch->setBaseUrl($this->requestInfo->getBasePath());
        $form  = $bridge->getForm();

        $batch->setFormId($form->getId());
        $batch->autoStart = true;

        // \MUtil\Registry\Source::$verbose = true;
        if (! $batch->isFinished()) {
            $batchRunner = $this->batchRunnerLoader->getBatchRunner($batch);
            // $batchRunner->setTitle($this->getTitleFor(3));
            $batchRunner->setJobInfo([]);

            $this->response = $batchRunner->getResponse($this->request);
            return;
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
                $element->h3($this->_('Track created successfully!'));
                $element->pInfo($this->_('Click the "Finish" button to see the track.'));
            }

            $data = $this->formData;

            // Remove unuseful data
            unset($data['button_spacer'], $data['current_step'], $data[$this->csrfName], $data['auto_form_focus_tracker']);

            // Add useful data

            ksort($data);

            $this->auditLog->logChange($this->request, null, array_filter($data));
        } else {
            $element->setValue($batch->getProgressPercentage());
        }

        $form->activateJQuery();
        $form->addElement($element);

        // \MUtil\EchoOut\EchoOut::track($this->loadImportData()->getArrayCopy());
    }

    protected function addStepElementsFor(FormBridgeInterface $bridge, DataReaderInterface $model, $step)
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

    protected function addStepFileCheck(FormBridgeInterface $bridge, DataReaderInterface $model)
    {
        if ($this->onStartStep() && $this->isNextClicked()) {
            return;
        }
        $this->nextDisabled = true;

        $this->displayHeader($bridge, $this->_('Checking the content of the file.'), 'h3');

        $batch = $this->getImportCheckBatch();
        $batch->setFormStep($this->currentStep - 1);
        $batch->setBaseUrl($this->requestInfo->getBasePath());
        $form  = $bridge->getForm();

        $batch->setFormId($form->getId());
        $batch->autoStart = true;

        // \MUtil\Registry\Source::$verbose = true;
        if (! $batch->isFinished()) {
            $batchRunner = $this->batchRunnerLoader->getBatchRunner($batch);
            // $batchRunner->setTitle($this->getTitleFor(3));
            $batchRunner->setJobInfo([]);

            $this->response = $batchRunner->getResponse($this->request);
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
                $element->h3($this->_('Import checks OK!'));
                $element->pInfo($this->_('Click the "Next" button to continue.'));
            }
        } else {
            $element->setValue($batch->getProgressPercentage());
        }

        $form->addElement($element);
    }

    protected function addStepFileImport(FormBridgeInterface $bridge, DataReaderInterface $model)
    {
        // Reset the data
        $this->sessionData['importData'] = null;

        $this->displayHeader($bridge, $this->_('Upload a track definition file.'), 'h3');

        $this->addItems($bridge, ['trackFile', 'gtr_id_track']);

        $element = $bridge->getForm()->getElement('trackFile');

        if ($element instanceof \Zend_Form_Element_File) {
            if (isset($this->sessionData['localfile']) && file_exists($this->sessionData['localfile'])) {
                unlink($this->sessionData['localfile']);
            }
            // Now add the rename filter, the localfile is known only once after loadFormData() has run
            $element->addFilter(new \Zend_Filter_File_Rename(array(
                'target'    => $this->sessionData['localfile'],
                'overwrite' => true
                )));

            $uploadFileName = $element->getFileName();

            // Download the data oon post with filename
            if ($this->requestInfo->isPost() && $uploadFileName && $element->isValid(null)) {
                // \MUtil\EchoOut\EchoOut::track($element->getFileName(), $element->getFileSize());
                if (!$element->receive()) {
                    throw new \MUtil\Model\ModelException(sprintf(
                        $this->_("Error retrieving file '%s'."),
                        $element->getFileName()
                        ));
                }

                $this->sessionData['importData'] = null;
                $this->sessionData['uploadFileName'] = basename($uploadFileName);
                // \MUtil\EchoOut\EchoOut::track($this->session->uploadFileName);
            }
        }

        $this->saveSession();
    }

    protected function addStepMergeTrack(FormBridgeInterface $bridge, DataReaderInterface $model)
    {
        // Things go really wrong (at the session level) if we run this code
        // while the finish button was pressed
        if ($this->isFinishedClicked()) {
            return;
        }

        $this->nextDisabled = true;

        $this->displayHeader($bridge, $this->_('Merging the tracks.'), 'h3');

        $batch = $this->getImportMergeBatch();
        $batch->setFormStep($this->currentStep);
        $batch->setBaseUrl($this->requestInfo->getBasePath());
        $form  = $bridge->getForm();

        $batch->setFormId($form->getId());
        $batch->autoStart = true;

        // \MUtil\Registry\Source::$verbose = true;
        if (! $batch->isFinished()) {
            $batchRunner = $this->batchRunnerLoader->getBatchRunner($batch);
            // $batchRunner->setTitle($this->getTitleFor(3));
            $batchRunner->setJobInfo([]);

            $this->response = $batchRunner->getResponse($this->request);
            return;
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
                $element->h3($this->_('Tracks mergeded successfully!'));
                $element->pInfo($this->_('Click the "Finish" button to see the merged track.'));
            }

            $data = $this->formData;

            // Remove unuseful data
            unset($data['button_spacer'], $data['current_step'], $data[$this->csrfName], $data['auto_form_focus_tracker']);

            // Add useful data

            ksort($data);

            $this->auditLog->logChange($this->request, null, array_filter($data));
        } else {
            $element->setValue($batch->getProgressPercentage());
        }

        $form->activateJQuery();
        $form->addElement($element);
    }

    protected function addStepRoundMatch(FormBridgeInterface $bridge, DataReaderInterface $model)
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
                $sModel = new \MUtil\Model\TableModel('gems__surveys');
                \Gems\Model::setChangeFieldsByPrefix($sModel, 'gus', $this->currentUser->getUserId());
                $sModel->saveAll($saves);

                $count = $sModel->getChanged();

                if ($count == 0) {
                    $this->addMessage($this->_('No export code changed'));
                } else {
                    $this->cache->invalidateTags(['surveys']);
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
        $this->cache->invalidateTags(['tracks']);
    }

    /**
     * Creates an empty form. Allows overruling in sub-classes.
     *
     * @param mixed $options
     * @return \Zend_Form
     */
    protected function createForm($options = null)
    {
        $form = new \Gems\Form($options);

        return $form;
    }

    protected function createModel(): DataReaderInterface
    {
        if (! isset($this->importModel)) {

            /**
             * @var SessionModel $dataModel
             */
            $dataModel = $this->metaModelLoader->createModel(SessionModel::class, 'import_for_' . $this->requestInfo->getCurrentController(), $this->session);
            $metaModel = $dataModel->getMetaModel();

            $metaModel->set('trackFile', [
                'label'         => $this->_('A .track.txt file'),
                'count'         => 1,
                'elementClass'  => 'File',
                'extension'     => 'txt',
                'description'   => $this->_('Import an exported track using a file with the extension ".track.txt".'),
                'required'      => true,
                ]);

            $metaModel->set('importId');

            $options = array('' => $this->_('<<create a new track>>')) + $this->trackDataRepository->getAllTracks();
            $metaModel->set('gtr_id_track', [
                'label'        => $this->_('Merge with'),
                'description'  => $this->_('Create a new track or choose a track to merge the import into.'),
                'default'      => '',
                'multiOptions' => $options,
                'size'         => min(12, count($options) + 1),
                ]);

            /**
             * @vvar TrackModel $trackModel
             */
            $trackModel = $this->metaModelLoader->createModel(TrackModel::class);
            $trackModel->applyFormatting(true, true);
            $metaModel->set('gtr_track_name', $trackModel->getMetaModel()->get('gtr_track_name') + array('respondentData' => true));
            $metaModel->set('gtr_organizations', $trackModel->getMetaModel()->get('gtr_organizations') + array('respondentData' => true));

            $this->importModel = $dataModel;
        }

        return $this->importModel;
    }


    protected function displayHeader(FormBridgeInterface $bridge, mixed $header, string $tagName = 'h2')
    {
        static $count = 0;

        $count += 1;
        $element = $bridge->getForm()->createElement('html', 'step_header_' . $count);
        $element->$tagName($header);

        $bridge->addElement($element);
    }

    protected function getImportCheckBatch(): TaskRunnerBatch
    {
        $metaModel = $this->importModel->getMetaModel();
        $batch = new TrackImportRunnerBatch('import_check_' . $metaModel->getName(), $this->overloader, $this->session);
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

            foreach ($import['conditions'] as $lineNr => $conditionData) {
                $batch->addTask(
                    'Tracker\\Import\\CheckTrackRoundConditionImportTask',
                    $lineNr,
                    $conditionData
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
     * @return \Gems\Task\TaskRunnerBatch
     */
    protected function getImportCreateBatch()
    {
        $metaModel = $this->importModel->getMetaModel();
        $batch = new TrackImportRunnerBatch('import_create_' . $metaModel->getName(), $this->overloader, $this->session);
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

            foreach ($import['conditions'] as $lineNr => $conditionData) {
                $batch->addTask(
                        'Tracker\\Import\\CreateTrackRoundConditionImportTask',
                        $lineNr,
                        $conditionData
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
     * @return \Gems\Task\TaskRunnerBatch
     */
    protected function getImportMergeBatch()
    {
        $metaModel = $this->importModel->getMetaModel();
        $batch = new TrackImportRunnerBatch('import_merge_' . $metaModel->getName(), $this->overloader, $this->session);
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

            foreach ($import['conditions'] as $lineNr => $conditionData) {
                $batch->addTask(
                        'Tracker\\Import\\CreateTrackRoundConditionImportTask',
                        $lineNr,
                        $conditionData
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
            'conditions'    => false,
            'rounds'        => false,
            );
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
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
    protected function loadFormData(): array
    {
        $model = $this->getModel();

        if ($this->requestInfo->isPost()) {
            $this->formData = $model->loadPostData($this->requestInfo->getRequestPostParams() + $this->formData, true);

        } else {
            // Assume that if formData is set it is the correct formData
            if (! $this->formData)  {
                $this->formData = $model->loadNew();
            }
        }

        if (isset($this->formData['gtr_id_track']) && $this->formData['gtr_id_track']) {
            $this->trackEngine = $this->tracker->getTrackEngine($this->formData['gtr_id_track']);
        }
        if (! (isset($this->formData['importId']) && $this->formData['importId'])) {
            $this->formData['importId'] = mt_rand(10000,99999) . time();
        }
        if (! (isset($this->sessionData['localfile']) && $this->sessionData['localfile'])) {
            $this->sessionData['localfile'] = File::createTemporaryIn(
                $this->importRepository->getImportTempDir(),
                $this->requestInfo->getCurrentController() . '_'
                );
            $this->saveSession();
        }

        // \MUtil\EchoOut\EchoOut::track($this->formData);
        return $this->formData;
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
        if (isset($this->sessionData['importData']) && ($this->sessionData['importData'] instanceof \ArrayObject)) {
            // No need to run this after initial load, but we need to
            // run this every time afterwards.
            $this->addImportToModelData($this->sessionData['importData']);
            return $this->sessionData['importData'];
        }

        $sections = $this->getImportSections();

        // Array object to have automatic passing by reference
        $this->sessionData['importData'] = new \ArrayObject(array_fill_keys(
                array_keys($sections) + array('errors'),
                array()
                ));

        $file = $this->sessionData['localfile'];

        if (! file_exists($file)) {
            $this->sessionData['importData']['errors'][] = sprintf(
                    $this->_('The import file "%s" seems to be missing.'),
                    $this->sessionData['uploadFileName']
                    );
            $this->saveSession();
            return $this->sessionData['importData'];
        }

        $content = file_get_contents($file);

        if (! $content) {
            $this->sessionData['importData']['errors'][] = sprintf(
                    $this->_('The import file "%s" is empty.'),
                    $this->sessionData['uploadFileName']
                    );
            $this->saveSession();
            return $this->sessionData['importData'];
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
                        $this->sessionData['importData']['errors'][] = sprintf(
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
                            $this->sessionData['importData'][$key][$lineNr] = $data;
                        } else {
                            $this->sessionData['importData']['errors'][] = sprintf(
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
        $this->saveSession();
        return $this->sessionData['importData'];
    }

    protected function saveSession(): void
    {
        $this->session->set($this->sessionId, $this->sessionData);
    }

    protected function setAfterSaveRoute()
    {
        if (isset($this->sessionData['localfile']) && file_exists($this->sessionData['localfile'])) {
            // Now is a good moment to remove the temporary file
            @unlink($this->sessionData['localfile']);
        }

        parent::setAfterSaveRoute();
    }

    protected function validateForm(array $formData): bool
    {
        if (2 == $this->currentStep) {
            return true;
        }
        // Note we use an \MUtil\Form
        return $this->_form->isValid($formData, $this->disableValidatorTranslation);
    }
}
