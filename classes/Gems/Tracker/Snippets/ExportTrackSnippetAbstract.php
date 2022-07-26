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

use Gems\Cache\HelperAdapter;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker\Snippets
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Jan 4, 2016 11:20:07 AM
 */
class ExportTrackSnippetAbstract extends \MUtil\Snippets\WizardFormSnippetAbstract
{
    /**
     *
     * @var \Gems\Task\TaskRunnerBatch
     */
    private $_batch;

    /**
     *
     * @var \Gems\AccessLog
     */
    protected $accesslog;

    /**
     *
     * @var HelperAdapter
     */
    protected $cache;

    /**
     *
     * @var \Gems\User\User
     */
    protected $currentUser;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * The number of seconds to wait before the file download starts
     *
     * @var int
     */
    protected $downloadWaitSeconds = 1;

    /**
     *
     * @var \MUtil\Model\ModelAbstract
     */
    protected $exportModel;

    /**
     *
     * @var \Gems\Loader
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
     * @var \Gems\Tracker\Engine\TrackEngineInterface
     */
    protected $trackEngine;

    /**
     *
     * @var \Gems\Util
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
     * @param \MUtil\Model\Bridge\FormBridgeInterface $bridge
     * @param \MUtil\Model\ModelAbstract $model
     * @param int $step The current step
     */
    protected function addStepElementsFor(\MUtil\Model\Bridge\FormBridgeInterface $bridge, \MUtil\Model\ModelAbstract $model, $step)
    {
        $this->displayHeader($bridge, sprintf(
                $this->_('%s track export. Step %d of %d.'),
                $this->trackEngine->getTrackName(),
                $step,
                $this->getStepCount()), 'h2');

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
     * @param \MUtil\Model\Bridge\FormBridgeInterface $bridge
     * @param \MUtil\Model\ModelAbstract $model
     */
    protected function addStepExportCodes(\MUtil\Model\Bridge\FormBridgeInterface $bridge, \MUtil\Model\ModelAbstract $model)
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
     * @param \MUtil\Model\Bridge\FormBridgeInterface $bridge
     * @param \MUtil\Model\ModelAbstract $model
     */
    protected function addStepExportSettings(\MUtil\Model\Bridge\FormBridgeInterface $bridge, \MUtil\Model\ModelAbstract $model)
    {
        $this->displayHeader($bridge, $this->_('Select what to export'), 'h3');

        $this->addItems($bridge, 'orgs', 'fields', 'rounds');
    }

    /**
     * Add the elements from the model to the bridge for the current step
     *
     * @param \MUtil\Model\Bridge\FormBridgeInterface $bridge
     * @param \MUtil\Model\ModelAbstract $model
     */
    protected function addStepGenerateExportFile(\MUtil\Model\Bridge\FormBridgeInterface $bridge, \MUtil\Model\ModelAbstract $model)
    {
        // Things go really wrong (at the session level) if we run this code
        // while the finish button was pressed
        if ($this->isFinishedClicked()) {
            return;
        }
        $this->displayHeader($bridge, $this->_('Creating the export file'), 'h3');

        $this->nextDisabled = true;

        $batch = $this->getExportBatch();
        $form  = $bridge->getForm();

        $batch->setFormId($form->getId());
        $batch->autoStart = true;

        // \MUtil\Registry\Source::$verbose = true;
        if ($batch->run($this->request)) {
            exit;
        }

        $element = $form->createElement('html', $batch->getId());

        if ($batch->isFinished()) {
            $this->nextDisabled = $batch->getCounter('export_errors');
            $batch->autoStart   = false;

            // Keep the filename after $batch->getMessages(true) cleared the previous
            $downloadName  = \MUtil\File::cleanupName($this->trackEngine->getTrackName()) . '.track.txt';
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
            $this->accesslog->logChange($this->request, null, array_filter($data));

            if ($this->nextDisabled) {
                $element->pInfo($this->_('Export errors occurred.'));
            } else {
                $p = $element->pInfo($this->_('Export file generated.'), ' ');
                $p->sprintf(
                        $this->plural(
                                'Click here if the download does not start automatically in %d second:',
                                'Click here if the download does not start automatically in %d seconds:',
                                $this->downloadWaitSeconds
                                ),
                        $this->downloadWaitSeconds
                        );
                $p->append(' ');

                $href = new \MUtil\Html\HrefArrayAttribute(array('file' => 'go', $this->stepFieldName => 'download'));
                $p->a(
                        $href,
                        $downloadName,
                        array('type' => 'application/download')
                        );

                $metaContent = sprintf('%d;url=%s', $this->downloadWaitSeconds, $href->render($this->view));
                $this->view->headMeta($metaContent, 'refresh', 'http-equiv');
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
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel()
    {
        if (! $this->exportModel instanceof \MUtil\Model\ModelAbstract) {
            $yesNo = $this->util->getTranslated()->getYesNo();

            $model = new \MUtil\Model\SessionModel('export_for_' . $this->request->getControllerName());

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
     * @param \MUtil\Model\Bridge\FormBridgeInterface $bridge
     * @param mixed $header Header content
     * @param string $tagName
     */
    protected function displayHeader(\MUtil\Model\Bridge\FormBridgeInterface $bridge, $header, $tagName = 'h2')
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
     * @param \MUtil\Model\Bridge\FormBridgeInterface $bridge
     * @param \MUtil\Model\ModelAbstract $model
     */
    protected function downloadExportFile()
    {
        $this->view->layout()->disableLayout();
        \Zend_Controller_Action_HelperBroker::getExistingHelper('viewRenderer')->setNoRender(true);

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
        if ($this->_batch) {
            return $this->_batch;
        }

        $this->_batch = $this->loader->getTaskRunnerBatch('track_export_' . $this->trackEngine->getTrackId());

        if ((! $load) || $this->_batch->isFinished()) {
            return $this->_batch;
        }

        if (! $this->_batch->isLoaded()) {
            $filename = \MUtil\File::createTemporaryIn(GEMS_ROOT_DIR . '/var/tmp/export/track');
            $trackId  = $this->trackEngine->getTrackId();
            $this->_batch->setSessionVariable('filename', $filename);

            $this->_batch->addTask('Tracker\\Export\\ProjectVersionExportTask');

            $this->_batch->addTask(
                    'Tracker\\Export\\MainTrackExportTask',
                    $this->trackEngine->getTrackId(),
                    $this->formData['orgs']
                    );

            if (isset($this->formData['fields']) && is_array($this->formData['fields'])) {
                // \MUtil\EchoOut\EchoOut::track($this->formData['fields']);
                foreach ($this->formData['fields'] as $fieldId) {
                    $this->_batch->addTask(
                            'Tracker\\Export\\TrackFieldExportTask',
                            $trackId,
                            $fieldId
                            );
                }
            }

            $model = $this->getModel();
            foreach ($model->getCol('surveyId') as $surveyId) {
                $this->_batch->addTask(
                        'Tracker\\Export\\TrackSurveyExportTask',
                        $trackId,
                        $surveyId
                        );
            }

            if (isset($this->formData['rounds']) && is_array($this->formData['rounds'])) {
                $this->_batch->addTask(
                            'Tracker\\Export\\TrackRoundConditionExportTask',
                            $trackId
                            );

                foreach ($this->formData['rounds'] as $roundId) {
                    $this->_batch->addTask(
                            'Tracker\\Export\\TrackRoundExportTask',
                            $trackId,
                            $roundId
                            );
                }
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
        // \MUtil\EchoOut\EchoOut::track($this->formData);
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

       // Default is just go to the index
        if ($this->routeAction && ($this->request->getActionName() !== $this->routeAction)) {
            $this->afterSaveRouteUrl = array(
                $this->request->getControllerKey() => $this->request->getControllerName(),
                $this->request->getActionKey()     => $this->routeAction,
                \MUtil\Model::REQUEST_ID           => $this->request->getParam(\MUtil\Model::REQUEST_ID),
                );
        }

        return $this;
    }
}
