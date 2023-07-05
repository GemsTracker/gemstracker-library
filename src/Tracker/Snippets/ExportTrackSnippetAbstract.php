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

use Gems\Batch\BatchRunnerLoader;
use Gems\Cache\HelperAdapter;
use Gems\Model\MetaModelLoader;
use Gems\Task\TrackExportRunnerBatch;
use Gems\Tracker\Engine\TrackEngineInterface;
use Gems\Util\Translated;
use Gems\Validator\ValidateSurveyExportCode;
use Laminas\Diactoros\Response\TextResponse;
use Mezzio\Session\SessionInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\File\File;
use Zalt\Loader\ProjectOverloader;
use Zalt\Message\MessengerInterface;
use Zalt\Model\Bridge\FormBridgeInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\Ra\SessionModel;
use Zalt\Model\Sql\SqlTableModel;
use Zalt\Snippets\Zend\ZendFormSnippetTrait;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker\Snippets
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Jan 4, 2016 11:20:07 AM
 */
class ExportTrackSnippetAbstract extends \Zalt\Snippets\WizardFormSnippetAbstract
{
    use ZendFormSnippetTrait;

    /**
     *
     * @var \Gems\Audit\AuditLog
     * /
    protected $accesslog;

    /**
     *
     * @var \Gems\Task\TrackExportRunnerBatch
     */
    protected TrackExportRunnerBatch $batch;

    /**
     *
     * @var HelperAdapter
     */
    protected $cache;

    /**
     *
     * @var \Gems\User\User
     * /
    protected $currentUser;

    /**
     * The number of seconds to wait before the file download starts
     *
     * @var int
     */
    protected int $downloadWaitSeconds = 1;

    /**
     *
     * @var SessionModel
     */
    protected SessionModel $exportModel;

    protected ?ResponseInterface $response;

    /**
     *
     * @var \Gems\Tracker\Engine\TrackEngineInterface
     */
    protected TrackEngineInterface $trackEngine;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        CacheItemPoolInterface $cache,
        protected BatchRunnerLoader $batchRunnerLoader,
        protected MetaModelLoader $metaModelLoader,
        protected ProjectOverloader $overloader,
        protected ServerRequestInterface $request,
        protected SessionInterface $session,
        protected Translated $translatedUtil,
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger);

        $this->cache = $cache;
    }

    /**
     * Add the elements from the model to the bridge for the current step
     *
     * @param \Zalt\Model\Bridge\FormBridgeInterface $bridge
     * @param \Zalt\Model\Data\DataReaderInterface $model
     * @param int $step The current step
     */
    protected function addStepElementsFor(FormBridgeInterface $bridge, DataReaderInterface $model, $step)
    {
        $this->displayHeader($bridge, $this->getTitleFor($step), 'h2');

        switch ($step) {
            case 2:
                $this->addStepExportCodes($bridge, $model);
                break;

            case 3:
                $this->addStepGenerateExportFile($bridge, $model);
                break;

            case 4:
                $this->addStepDownloadExportFile($bridge, $model);
                break;

            default:
                $this->addStepExportSettings($bridge, $model);
                break;

        }
    }

    /**
     * Add the elements from the model to the bridge for the current step
     *
     * @param \Zalt\Model\Bridge\FormBridgeInterface $bridge
     * @param \Zalt\Model\Data\DataReaderInterface $model
     */
    protected function addStepDownloadExportFile(FormBridgeInterface $bridge, DataReaderInterface $model)
    {
        // Things go really wrong (at the session level) if we run this code
        // while the finish button was pressed
        if ($this->isFinishedClicked()) {
            return;
        }

        $batch = $this->getExportBatch();
        $batch->setBaseUrl($this->requestInfo->getBasePath());
        if ($batch->isFinished()) {
            $this->nextDisabled = $batch->getCounter('export_errors');
            $batch->autoStart   = false;

            // Keep the filename after $batch->getMessages(true) cleared the previous
            $downloadName  = File::cleanupName($this->trackEngine->getTrackName()) . '.track.txt';
            $localFilename = $batch->getSessionVariable('filename');

            $this->addMessage($batch->getMessages(true));
            $batch->setSessionVariable('downloadname', $downloadName);
            $batch->setSessionVariable('filename', $localFilename);

            // Log Export
            $data = $this->formData;
            // Remove unuseful data
            unset($data['button_spacer'], $data['current_step'], $data[$this->csrfId]);
            // Add useful data
            $data['localfile']    = '...' . substr($localFilename, -30);
            $data['downloadname'] = $downloadName;
            ksort($data);
            // $this->accesslog->logChange($this->requestInfo, null, array_filter($data));

            $headers['Content-Type'] = "application/download";
            $headers['Content-Disposition'] = "attachment; filename=\"" . $downloadName . "\"";

            $this->response = new TextResponse(
                file_get_contents($localFilename),
                200,
                $headers
            );
        }
    }

    /**
     * Add the elements from the model to the bridge for the current step
     *
     * @param \Zalt\Model\Bridge\FormBridgeInterface $bridge
     * @param \Zalt\Model\Data\DataReaderInterface $model
     */
    protected function addStepExportCodes(FormBridgeInterface $bridge, DataReaderInterface $model)
    {
        $this->displayHeader($bridge, $this->_('Set the survey export codes'), 'h3');

        $rounds      = $this->formData['rounds'];
        $surveyCodes = array();
        $surveyModel = $this->getSurveyTableModel();

        foreach ($rounds as $roundId) {
            $round = $this->trackEngine->getRound($roundId);
            $sid   = $round->getSurveyId();
            $name  = ValidateSurveyExportCode::START_NAME . $sid;

            $surveyCodes[$name] = $name;
            $model->getMetaModel()->set($name, 'validator', new ValidateSurveyExportCode(
                $sid, $surveyModel
                ));
        }
        $this->addItems($bridge, $surveyCodes);
    }

    /**
     * Add the elements from the model to the bridge for the current step
     *
     * @param \Zalt\Model\Bridge\FormBridgeInterface $bridge
     * @param \Zalt\Model\Data\DataReaderInterface $model
     */
    protected function addStepExportSettings(FormBridgeInterface $bridge, DataReaderInterface $model)
    {
        $this->displayHeader($bridge, $this->_('Select what to export'), 'h3');

        $this->addItems($bridge, ['orgs', 'fields', 'rounds']);
    }

    /**
     * Add the elements from the model to the bridge for the current step
     *
     * @param \Zalt\Model\Bridge\FormBridgeInterface $bridge
     * @param \Zalt\Model\Data\DataReaderInterface $model
     */
    protected function addStepGenerateExportFile(FormBridgeInterface $bridge, DataReaderInterface $model)
    {
        // Things go really wrong (at the session level) if we run this code
        // while the finish button was pressed
        if ($this->isFinishedClicked()) {
            return;
        }
        $this->displayHeader($bridge, $this->_('Creating the export file'), 'h3');

        $this->nextDisabled = true;

        $batch = $this->getExportBatch();
        $batch->setBaseUrl($this->requestInfo->getBasePath());
        $form  = $bridge->getForm();

        $batch->setFormId($form->getId());
        $batch->autoStart = true;

        if (! $batch->isFinished()) {
            $batchRunner = $this->batchRunnerLoader->getBatchRunner($batch);
            $batchRunner->setTitle($this->getTitleFor(3));
            $batchRunner->setJobInfo([]);

            $this->response = $batchRunner->getResponse($this->request);
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
        if (2 == $step) {
            $metaModel = $this->exportModel->getMetaModel();
            $saves = array();
            foreach ($metaModel->getCol('surveyId') as $name => $sid) {
                if (isset($this->formData[$name]) && $this->formData[$name]) {
                    $saves[] = array('gsu_id_survey' => $sid, 'gsu_export_code' => $this->formData[$name]);
                }
            }

            if ($saves) {
                $sModel = $this->getSurveyTableModel();
                foreach ($saves as $save) {
                    $sModel->save($save);
                }

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
        if (!isset($options['class'])) {
            $options['class'] = 'form-horizontal';
        }

        if (!isset($options['role'])) {
            $options['role'] = 'form';
        }
        return new \Gems\Form($options);
    }

    /**
     * Creates the model
     *
     * @return \Zalt\Model\Data\DataReaderInterface
     */
    protected function createModel(): DataReaderInterface
    {
        if (! isset($this->exportModel)) {
            $yesNo = $this->translatedUtil->getYesNo();

            $dataModel = $this->metaModelLoader->createModel(SessionModel::class, 'export_for_' . $this->requestInfo->getCurrentController(), $this->session);
            $model = $dataModel->getMetaModel();

            $model->set('orgs', 'label', $this->_('Organization export'),
                    'default', 1,
                    'description', $this->_('Export the organizations for which the track is active'),
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
                        $model->set(ValidateSurveyExportCode::START_NAME . $survey->getSurveyId(),
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

            $this->exportModel = $dataModel;
        }

        return $this->exportModel;
    }

    /**
     * Display a header
     *
     * @param \Zalt\Model\Bridge\FormBridgeInterface $bridge
     * @param mixed $header Header content
     * @param string $tagName
     */
    protected function displayHeader(FormBridgeInterface $bridge, $header, $tagName = 'h2')
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
     * @param \Zalt\Model\Bridge\FormBridgeInterface $bridge
     * @param \Zalt\Model\Data\DataReaderInterface $model
     */
    protected function downloadExportFile()
    {
        // $this->view->layout()->disableLayout();
        // \Zend_Controller_Action_HelperBroker::getExistingHelper('viewRenderer')->setNoRender(true);

        $batch          = $this->getExportBatch(false);
        $downloadName  = $batch->getSessionVariable('downloadname');
        $localFilename = $batch->getSessionVariable('filename');

        header("Content-Type: application/download");
        header("Content-Disposition: attachment; filename=\"$downloadName\"");
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Pragma: cache");                          // HTTP/1.0
        readfile($localFilename);
        exit();
    }

    /**
     *
     * @return \Gems\Task\TaskRunnerBatch
     */
    protected function getExportBatch($load = true)
    {
        if (isset($this->batch)) {
            return $this->batch;
        }

        $metaModel = $this->exportModel->getMetaModel();
        // $this->batch = $this->loader->getTaskRunnerBatch('track_export_' . $this->trackEngine->getTrackId());
        $this->batch = new TrackExportRunnerBatch('export_data_' . $metaModel->getName(), $this->overloader, $this->session);

        if ((! $load) || $this->batch->isFinished()) {
            return $this->batch;
        }

        if (! $this->batch->isLoaded()) {
            $filename = File::createTemporaryIn(); // $this->config['rootDir'] . '/var/tmp/export/track');
            $trackId  = $this->trackEngine->getTrackId();
            $this->batch->setSessionVariable('filename', $filename);

            $this->batch->addTask('Tracker\\Export\\ProjectVersionExportTask');

            $this->batch->addTask(
                    'Tracker\\Export\\MainTrackExportTask',
                    $this->trackEngine->getTrackId(),
                    $this->formData['orgs']
                    );

            if (isset($this->formData['fields']) && is_array($this->formData['fields'])) {
                foreach ($this->formData['fields'] as $fieldId) {
                    $this->batch->addTask(
                            'Tracker\\Export\\TrackFieldExportTask',
                            $trackId,
                            $fieldId
                            );
                }
            }

            foreach ($metaModel->getCol('surveyId') as $surveyId) {
                $this->batch->addTask(
                        'Tracker\\Export\\TrackSurveyExportTask',
                        $trackId,
                        $surveyId
                        );
            }

            if (isset($this->formData['rounds']) && is_array($this->formData['rounds'])) {
                $this->batch->addTask(
                            'Tracker\\Export\\TrackRoundConditionExportTask',
                            $trackId
                            );

                foreach ($this->formData['rounds'] as $roundId) {
                    $this->batch->addTask(
                            'Tracker\\Export\\TrackRoundExportTask',
                            $trackId,
                            $roundId
                            );
                }
            }
        } else {
            $filename = $this->batch->getSessionVariable('filename');
        }

        $this->batch->setVariable('file', fopen($filename, 'a'));

        return $this->batch;
    }

    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    public function getTitleFor(int $step)
    {
        return sprintf(
            $this->_('%s track export. Step %d of %d.'),
            $this->trackEngine->getTrackName(),
            $step,
            $this->getStepCount(),
            );
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

    public function getSurveyTableModel(): SqlTableModel
    {
        static $surveyModel;

        if (! $surveyModel) {
            $surveyModel = $this->metaModelLoader->createModel(SqlTableModel::class, 'gems__surveys');
            $this->metaModelLoader->setChangeFields($surveyModel->getMetaModel(), 'gus');
        }

        return $surveyModel;
    }

    public function hasHtmlOutput(): bool
    {
        if (parent::hasHtmlOutput()) {
            if (! isset($this->response)) {
                return true;
            }
        }

        return false;
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

        } elseif ('download' == $this->requestInfo->getParam($this->stepFieldName)) {
            $this->formData = $this->requestInfo->getParams();
            $this->downloadExportFile();

        } else {
            // Assume that if formData is set it is the correct formData
            if (! $this->formData)  {
                $this->formData = $model->loadNew();
            }
        }

        // Step can be defined in get paramter
        $step = $this->requestInfo->getParam($this->stepFieldName);
        if ($step) {
            $this->formData[$this->stepFieldName] = $step;
        }
        return $this->formData;
    }

    /**
     * Set what to do when the form is 'finished'.
     *
     * @return \Gems\Tracker\Snippets\ExportTrackSnippetAbstract
     */
    protected function setAfterSaveRoute()
    {
        $filename = $this->getExportBatch(false)->getSessionVariable('filename');
        if ($filename) {
            // Now is a good moment to remove the temporary file
            @unlink($filename);
        }

        return $this;
    }
}
