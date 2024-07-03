<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Snippets\Import
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Snippets\Import;

use Gems\Model\SurveyMaintenanceModel;
use Gems\Model\Translator\SurveySettingsImportTranslator;

/**
 * @package    Gems
 * @subpackage Snippets\Import
 * @since      Class available since version 1.0
 */
class ImportSurveySettingsSnippet extends \MUtil_Snippets_WizardFormSnippetAbstract
{
    /**
     * Contains the errors generated so far
     *
     * @var array
     */
    private $_errors = array();

    /**
     *
     * @var \Zend_Session_Namespace
     */
    protected $_session;

    /**
     * Css class for messages and errors
     *
     * @var string
     */
    protected $errorClass = 'errors';

    /**
     * @var \Gems_Events
     */
    protected $events;

    /**
     * Class for import fields table
     *
     * @var string
     */
    protected $formatBoxClass = 'browser table';

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
     * @var SurveyMaintenanceModel
     */
    protected $model;

    /**
     * @var \MUtil_Model_TabbedTextModel
     */
    protected $sourceModel;

    /**
     * @var \Gems_Tracker
     */
    protected $tracker;

    /**
     * @param \MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @return void
     * @throws \Gems_Exception_Coding
     * @throws \MUtil_Model_ModelException
     * @throws \Zend_Filter_Exception
     */
    protected function addStep1(\MUtil_Model_Bridge_FormBridgeInterface $bridge, \MUtil_Model_ModelAbstract $model)
    {
        $this->_session->importData = null;

        $this->displayHeader($bridge, $this->_('Upload a survey settings file.'), 'h3');

        $this->addItems($bridge, 'settingsFile');

        $element = $bridge->getForm()->getElement('settingsFile');

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
     * Add the elements from the model to the bridge for the current step
     *
     * @param \MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function addStep2(\MUtil_Model_Bridge_FormBridgeInterface $bridge, \MUtil_Model_ModelAbstract $model)
    {
        $this->loadSourceModel();

        $this->displayHeader($bridge, $this->_('Upload successful!'));
        $this->displayErrors($bridge, $this->_('Check the input visually.'));

        $element = $bridge->getForm()->createElement('html', 'importdisplay');

        $repeater = \MUtil_Lazy::repeat(new \LimitIterator($this->sourceModel->loadIterator(), 0, 20));
        $table    = new \MUtil_Html_TableElement($repeater, array('class' => $this->formatBoxClass));

        foreach ($this->sourceModel->getItemsOrdered() as $name) {
            $table->addColumn($repeater->$name, $this->sourceModel->get($name, 'label'));
        }

        // Extra div for CSS settings
        $element->setValue(new \MUtil_Html_HtmlElement('div', $table, array('class' => $this->formatBoxClass)));
        $bridge->addElement($element);
    }

    /**
     * Add the elements from the model to the bridge for the current step
     *
     * @param \MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function addStep3(\MUtil_Model_Bridge_FormBridgeInterface $bridge, \MUtil_Model_ModelAbstract $model)
    {
        $this->loadSourceModel();

        $this->displayHeader($bridge, $this->_('Link surveys!'));

        $this->_errors = [];
        $rows = [];
        foreach ($this->sourceModel->load() as $survey) {
            $rows[] = $this->addSurvey($survey);
        }

        if ($this->_errors) {
            $this->addMessage($this->_errors);
            $this->nextDisabled = true;
        }

        $this->addItems($bridge, $rows);
    }

    /**
     * Add the elements from the model to the bridge for the current step
     *
     * @param \MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function addStep4(\MUtil_Model_Bridge_FormBridgeInterface $bridge, \MUtil_Model_ModelAbstract $model)
    {
        $this->loadSourceModel();

        $this->displayHeader($bridge, $this->_('Saving import result.'));
        $saved   = \MUtil_Html_ListElement::ol();
        $skipped = \MUtil_Html_ListElement::ol();
        foreach ($this->sourceModel->load() as $survey) {
            if ($this->saveSurvey($survey)) {
                $saved[] = $survey['gsu_survey_name'];
            } else {
                $skipped[] = $survey['gsu_survey_name'];
            }
        }

        $changes = \MUtil_Html::div();
        if ($saved->count()) {
            $changes->pInfo($this->_('Surveys changed:'));
            $changes->append($saved);
        } else {
            $changes->pInfo($this->_('No surveys changed!'));
        }
        if ($skipped->count()) {
            $changes->pInfo($this->_('Surveys skipped:'));
            $changes->append($skipped);
        }

        $element = $bridge->getForm()->createElement('html', 'resultdisplay');

        // Extra div for CSS settings
        $element->setValue(new \MUtil_Html_HtmlElement('div', $changes, array('class' => $this->formatBoxClass)));
        $bridge->addElement($element);
    }

    /**
     * @inheritDoc
     */
    protected function addStepElementsFor(\MUtil_Model_Bridge_FormBridgeInterface $bridge, \MUtil_Model_ModelAbstract $model, $step)
    {
        $this->displayHeader($bridge, $this->getFormTitle($step), 'h2');

        switch ($step) {
            case 2:
                $this->addStep2($bridge, $model);
                break;

            case 3:
                $this->addStep3($bridge, $model);
                break;

            case 4:
                $this->addStep4($bridge, $model);
                break;

            // Intentional fall through
            default:
                $this->addStep1($bridge, $model);
                break;

        }
    }

    protected function addSurvey(array $survey)
    {
        // \MUtil_Echo::track($survey);
        $code = $survey['gsu_export_code'];
        $key  = $this->getKey($code);
        if (isset($survey['gsu_beforeanswering_event']) && $survey['gsu_beforeanswering_event']) {
            try {
                $this->events->loadSurveyBeforeAnsweringEvent(str_replace('\\\\', '\\', $survey['gsu_beforeanswering_event']));
            } catch (\Gems_Exception_Coding $e) {
                $this->_errors[] = sprintf($this->_('Export code %s has an error: %s'), $code, $e->getMessage());
            }
        }
        if (isset($survey['gsu_completed_event']) && $survey['gsu_completed_event']) {
            try {
                $this->events->loadSurveyCompletionEvent(str_replace('\\\\', '\\', $survey['gsu_completed_event']));
            } catch (\Gems_Exception_Coding $e) {
                $this->_errors[] = sprintf($this->_('Export code %s has an error: %s'), $code, $e->getMessage());
            }
        }
        if (isset($survey['gsu_display_event']) && $survey['gsu_display_event']) {
            try {
                $this->events->loadSurveyDisplayEvent(str_replace('\\\\', '\\', $survey['gsu_display_event']));
            } catch (\Gems_Exception_Coding $e) {
                $this->_errors[] = sprintf($this->_('Export code %s has an error: %s'), $code, $e->getMessage());
            }
        }
        $currentSurvey = $this->model->loadFirst(['gsu_export_code' => $code]);
        if ($currentSurvey) {
            $this->importModel->set($key, [
                'label'        => $code,
                'elementClass' => 'Exhibitor',
                'export_code'  => $code,
                'multiOptions' => [$currentSurvey['gsu_id_survey'] => $survey['gsu_survey_name']],
                'value'        => $currentSurvey['gsu_id_survey'],
            ]);
        } else {
            $currentSurveys = ['' => $this->_('<skip survey>')];
            $value = '';
            foreach ($this->model->load([['gsu_survey_name' => $survey['gsu_survey_name']], ['gsu_surveyor_id' => $survey['gsu_surveyor_id']]]) as $current) {
                $currentSurveys[$current['gsu_id_survey']] = $current['gsu_survey_name'];
                if (! $value) {
                    $value = $current['gsu_id_survey'];
                }
            }
            reset($currentSurveys);
            $this->importModel->set($key, [
                'label'        => $code,
                'export_code'  => $code,
                'multiOptions' => $currentSurveys,
                'value'        => $value,
            ]);
        }

        return $key;
    }

    public function afterRegistry()
    {
        parent::afterRegistry();

        $this->events = $this->loader->getEvents();
//        $this->tracker = $this->loader->getTracker();
    }

    /**
     * @inheritDoc
     */
    protected function createModel()
    {
        if (! $this->importModel instanceof \MUtil_Model_ModelAbstract) {

            $model = new \MUtil_Model_SessionModel('import_for_' . $this->request->getControllerName());

            $model->set('settingsFile', 'label', $this->_('A .settings.txt file'),
                'count', 1,
                'elementClass', 'File',
                'extension', 'txt',
                'description', $this->_('Import exported survey settings using a file with the extension ".settings.txt".'),
                'required', true
            );

            $model->set('importId');
            $model->set('html');

            $this->importModel = $model;
        }
        return $this->importModel;
    }

    /**
     * Display the errors
     *
     * @param \MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param array Errors to display
     */
    protected function displayErrors(\MUtil_Model_Bridge_FormBridgeInterface $bridge, $errors = null)
    {
        if (null === $errors) {
            $errors = $this->_errors;
        }

        if ($errors) {
            $element = $bridge->getForm()->createElement('html', 'errorlist');

            $element->ul($errors, array('class' => $this->errorClass));

            $bridge->addElement($element);
        }
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
     * Get the title at the top of the form
     *
     * @param int $step The current step
     * @return string
     */
    protected function getFormTitle($step)
    {
        return sprintf(
            $this->_('Survey settings import. Step %d of %d.'),
            $step,
            $this->getStepCount()
        );
    }

    protected function getKey($code)
    {
        return "gsu_export_code_$code";
    }

    /**
     * @inheritDoc
     */
    protected function getStepCount()
    {
        return 4;
    }

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
    }

    protected function loadSourceModel()
    {
        if (! $this->sourceModel) {
            $this->sourceModel = new \MUtil_Model_TabbedTextModel($this->_session->localfile);

            foreach ($this->sourceModel->loadFirst() as $name => $value) {
                $this->sourceModel->set($name, $this->model->get($name));
            }
        }
    }

    protected function saveSurvey(array $survey)
    {
        // \MUtil_Echo::track($survey);
        $code  = $survey['gsu_export_code'];
        $key   = $this->getKey($code);
        $value = $this->request->getParam($key);

        if (! $value) {
            return false;
        }

        $survey['gsu_id_survey'] = $value;

        // Remove data that should not be copied from import
        unset($survey['gsu_survey_name'], $survey['gsu_survey_description'], $survey['gsu_surveyor_id']);
        $changed = $this->model->getChanged();
        $this->model->save($survey);

        return $this->model->getChanged() > $changed;
    }
}