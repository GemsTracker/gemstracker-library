<?php

/**
 * Copyright (c) 2012, Erasmus MC
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
 * @package    MUtil
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $id: ModelImportSnippet.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 * generic import wizard.
 *
 * Set the targetModel (directly or through $this->model) and the
 * importTranslators and it should work.
 *
 * @package    MUtil
 * @subpackage Snippets
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.3
 */
class MUtil_Snippets_Standard_ModelImportSnippet extends MUtil_Snippets_WizardFormSnippetAbstract
{
    /**
     * Contains the errors generated so far
     *
     * @var array
     */
    private $_errors = array();

    /**
     * Marker for communicating a succesfull save of the data
     *
     * @var boolean
     */
    private $_saved = false;

    /**
     *
     * @var array
     */
    private $_translatorDescriptions;

    /**
     * Array key of the default import translator
     *
     * @var string
     */
    protected $defaultImportTranslator;

    /**
     * Css class for messages and errors
     *
     * @var string
     */
    protected $errorClass = 'errors';

    /**
     * The final directory when the data could not be imported.
     *
     * If empty the file is thrown away after the failure.
     *
     * Used only when importer is not set
     *
     * @var string
     */
    public $failureDirectory;

    /**
     * True when content is supplied from a file
     *
     * @var boolean
     */
    protected $fileMode = true;

    /**
     * Class for import fields table
     *
     * @var string
     */
    protected $formatBoxClass;

    /**
     *
     * @var MUtil_Model_Importer
     */
    protected $importer;

    /**
     *
     * @var MUtil_Model_ModelAbstract
     */
    protected $importModel;

    /**
     * Required, an array of one or more translators
     *
     * @var array of MUtil_Model_ModelTranslatorInterface objects
     */
    protected $importTranslators;

    /**
     * The filename minus the extension for long term storage.
     *
     * If empty the file is not kept.
     *
     * Used only when importer is not set
     *
     * @var string
     */
    protected $longtermFilename;

    /**
     *
     * @var MUtil_Model_ModelAbstract
     */
    protected $model;

    /**
     * Model to read import
     *
     * @var MUtil_Model_ModelAbstract
     */
    protected $sourceModel;

    /**
     * The final directory when the data was successfully imported.
     *
     * If empty the file is thrown away after the import.
     *
     * Used only when importer is not set
     *
     * @var string
     */
    public $successDirectory;

    /**
     * Model to save import into
     *
     * Required, can be set by passing a model to $this->model
     *
     * @var MUtil_Model_ModelAbstract
     */
    protected $targetModel;

    /**
     * The filepath for temporary files
     *
     * @var string
     */
    public $tempDirectory;

    /**
     *
     * @var Zend_View
     */
    public $view;

    /**
     * Add the elements from the model to the bridge for the current step
     *
     * @param MUtil_Model_FormBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     */
    protected function addStep1(MUtil_Model_FormBridge $bridge, MUtil_Model_ModelAbstract $model)
    {
        $this->addItems($bridge, 'trans', 'mode');
    }

    /**
     * Add the elements from the model to the bridge for the current step
     *
     * @param MUtil_Model_FormBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     */
    protected function addStep2(MUtil_Model_FormBridge $bridge, MUtil_Model_ModelAbstract $model)
    {
        $translator = $this->importer->getImportTranslator();
        if ($translator instanceof MUtil_Model_ModelTranslatorInterface) {
            $element = new MUtil_Form_Element_Html('trans_header');
            $element->span($this->_('Choosen translation definition: '));
            $element->strong($translator->getDescription());
            $element->setDecorators(array('Tooltip', 'ViewHelper'));
            $bridge->addElement($element);
        }

        if ($this->fileMode) {
            $this->addItems($bridge, 'file');

            $element = $bridge->getForm()->getElement('file');

            if ($element instanceof Zend_Form_Element_File) {
                // Now add the rename filter, the localfile is known only once after loadFormData() has run
                $element->addFilter(new Zend_Filter_File_Rename(array(
                    'target'    => $this->formData['localfile'],
                    'overwrite' => true
                    )));

                // Download the data (no test for post, step 2 is always a post)
                if ($element->getFileName()) {
                    // Now the filename is still set to the upload filename.
                    $this->formData['extension'] = pathinfo($element->getFileName(), PATHINFO_EXTENSION);

                    if (!$element->receive()) {
                        throw new MUtil_Model_ModelException(sprintf(
                            $this->_("Error retrieving file '%s'."),
                            $element->getFileName()
                            ));
                    }
                }
            }
        } else {
            $this->addItems($bridge, 'content');

            $this->formData['extension'] = 'txt';
            if (isset($this->formData['content']) && $this->formData['content']) {
                file_put_contents($this->formData['localfile'], $this->formData['content']);
            } else {
                if (filesize($this->formData['localfile']) && ('txt' === $this->formData['extension'])) {
                    $content = file_get_contents($this->formData['localfile']);
                } else {
                    $content = '';
                }

                if (!$content) {
                    // Add a default content if empty
                    $translator = $this->importer->getImportTranslator();
                    $fields = array_filter(array_keys($translator->getFieldsTranslations()), 'is_string');

                    $content = implode("\t", $fields) . "\n" .
                        str_repeat("\t", count($fields) - 1) . "\n";

                }
                $this->formData['content'] = $content;
            }
        }
    }

    /**
     * Add the elements from the model to the bridge for the current step
     *
     * @param MUtil_Model_FormBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     */
    protected function addStep3(MUtil_Model_FormBridge $bridge, MUtil_Model_ModelAbstract $model)
    {
        if ($this->loadSourceModel()) {
            $this->displayHeader($bridge, $this->_('Upload successful!'));
            $this->displayErrors($bridge, $this->_('Check the input visually.'));

            // MUtil_Echo::track($this->sourceModel->load());

            $element  = new MUtil_Form_Element_Html('importdisplay');
            $repeater = MUtil_Lazy::repeat(new LimitIterator($this->sourceModel->loadIterator(), 0, 20));
            $table    = new MUtil_Html_TableElement($repeater, array('class' => $this->formatBoxClass));

            foreach ($this->sourceModel->getItemsOrdered() as $name) {
                $table->addColumn($repeater->$name, $name);
            }

            // Extra div for CSS settings
            $element->setValue(new MUtil_Html_HtmlElement('div', $table, array('class' => $this->formatBoxClass)));
            $bridge->addElement($element);
        } else {
            $this->displayHeader($bridge, $this->_('Upload error!'));
            $this->displayErrors($bridge);

            $this->nextDisabled = true;
        }
    }

    /**
     * Add the elements from the model to the bridge for the current step
     *
     * @param MUtil_Model_FormBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     */
    protected function addStep4(MUtil_Model_FormBridge $bridge, MUtil_Model_ModelAbstract $model)
    {
        $this->nextDisabled = true;

        if ($this->loadSourceModel()) {
            $form  = $bridge->getForm();
            $batch = $this->importer->getCheckWithImportBatches();

            $batch->setFormId($form->getId());
            $batch->autoStart = true;

            // MUtil_Registry_Source::$verbose = true;
            if ($batch->run($this->request)) {
                exit;
            }

            $element = new MUtil_Form_Element_Html($batch->getId());

            if ($batch->isFinished()) {
                $this->nextDisabled = $batch->getCounter('import_errors');
                $batch->autoStart   = false;

                $this->addMessage($batch->getMessages(true));
                if ($this->nextDisabled) {
                    $element->pInfo($this->_('Import errors found, import is not allowed.'));
                } else {
                    $element->pInfo($this->_('Check was successfull, import can start.'));
                }

            } else {
                $element->setValue($batch->getPanel($this->view, $batch->getProgressPercentage() . '%'));

            }
            $form->activateJQuery();
            $form->addElement($element);
        } else {
            $this->displayHeader($bridge, $this->_('Check error!'));
            $this->displayErrors($bridge);

            $this->nextDisabled = true;
        }
    }

    /**
     * Add the elements from the model to the bridge for the current step
     *
     * @param MUtil_Model_FormBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     */
    protected function addStep5(MUtil_Model_FormBridge $bridge, MUtil_Model_ModelAbstract $model)
    {
        $this->nextDisabled = true;

        if ($this->loadSourceModel()) {
            $form  = $bridge->getForm();
            $batch = $this->importer->getImportOnlyBatch();

            $batch->setFormId($form->getId());
            $batch->autoStart = true;

            if ($batch->run($this->request)) {
                exit;
            }

            $element = new MUtil_Form_Element_Html($batch->getId());

            if ($batch->isFinished()) {
                $this->nextDisabled = $batch->getCounter('import_errors');
                $batch->autoStart   = false;

                $imported = $batch->getCounter('imported');
                $changed  = $batch->getCounter('changed');

                $text = sprintf($this->plural('%d row imported.', '%d rows imported.', $imported), $imported) . ' ' .
                        sprintf($this->plural('%d row changed.', '%d rows changed.', $changed), $changed);

                $this->addMessage($batch->getMessages(true));
                $this->addMessage($text);

                $element->pInfo($text);

            } else {
                $element->setValue($batch->getPanel($this->view, $batch->getProgressPercentage() . '%'));

            }
            $form->activateJQuery();
            $form->addElement($element);
        } else {
            $this->displayHeader($bridge, $this->_('Import error!'));
            $this->displayErrors($bridge);

            $this->nextDisabled = true;
        }


        return;
    }

    /**
     * Add the elements from the model to the bridge for the current step
     *
     * @param MUtil_Model_FormBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     * @param int $step The current step
     */
    protected function addStepElementsFor(MUtil_Model_FormBridge $bridge, MUtil_Model_ModelAbstract $model, $step)
    {
        $this->displayHeader(
                $bridge,
                sprintf($this->_('Data import. Step %d of %d.'), $step, $this->getStepCount()),
                'h1');

        switch ($step) {
            case 0:
            case 1:
                $this->addStep1($bridge, $model);
                break;

            case 2:
                $this->addStep2($bridge, $model);
                break;

            case 3:
                $this->addStep3($bridge, $model);
                break;

            case 4:
                $this->addStep4($bridge, $model);
                break;

            default:
                $this->addStep5($bridge, $model);
                break;

        }
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        if (! $this->importer instanceof MUtil_Model_Importer) {
            $this->importer = new MUtil_Model_Importer();

            $source = new MUtil_Registry_Source(get_object_vars($this));
            $source->applySource($this->importer);
            $this->importer->setRegistrySource($source);
        }
        if (! $this->targetModel instanceof MUtil_Model_ModelAbstract) {
            if ($this->model instanceof MUtil_Model_ModelAbstract) {
                $this->targetModel = $this->model;
            }
        }
        if ($this->targetModel instanceof MUtil_Model_ModelAbstract) {
            $this->importer->setTargetModel($this->targetModel);
        }
        if ($this->sourceModel instanceof MUtil_Model_ModelAbstract) {
            $this->importer->setSourceModel($this->sourceModel);
        }


        // Cleanup any references to model to avoid confusion
        $this->model = null;
    }

    /**
     * Overrule this function for any activities you want to take place
     * before the actual form is displayed.
     *
     * This means the form has been validated, step buttons where processed
     * and the current form will be the one displayed.
     *
     * @param int $step The current step
     */
    protected function beforeDisplayFor($step)
    {
        switch ($step) {
        case 1:
            $fieldInfo = $this->getTranslatorTable();
            break;

        case 2:
        case 3:
        case 4:
        case 5:
            if (isset($this->formData['trans']) && $this->formData['trans']) {
                $fieldInfo = $this->getTranslatorTable($this->formData['trans']);
                break;
            }

        default:
            $fieldInfo = null;
        }

        if ($fieldInfo) {
            $table = MUtil_Html_TableElement::createArray($fieldInfo, $this->_('Import field definitions'), true);
            $table->appendAttrib('class', $this->formatBoxClass);

            $element = new MUtil_Form_Element_Html('transtable');
            $element->setValue($table);

            $this->_form->addElement($element);
        }
    }

    /**
     * Creates the model
     *
     * @return MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        if (! $this->importModel instanceof MUtil_Model_ModelAbstract) {
            // $model = new MUtil_Model_TableModel
            $model = new MUtil_Model_SessionModel('import_for_' . $this->request->getControllerName());

            $model->set('trans', 'label', $this->_('Choose a translation definition'),
                    'multiOptions', $this->getTranslatorDescriptions(),
                    'required', true,
                    'elementClass', 'Radio',
                    'separator', ' ');

            $model->set('mode', 'label', $this->_('Choose work mode'),
                    'multiOptions', array(
                        'file'     => $this->_('Upload a file'),
                        'textarea' => $this->_('Copy and paste into a text field'),
                    ),
                    'required', true,
                    'elementClass', 'Radio',
                    'separator', ' ');

            $model->set('file', 'label', $this->_('Import file'),
                    'count',        1,
                    'elementClass', 'File',
                    'extension',    'txt,xml',
                    'required',     true);

            if ($this->tempDirectory) {
                MUtil_File::ensureDir($this->tempDirectory);
                $model->set('file', 'destination',  $this->tempDirectory);
            }

            // Storage for local copy of the file, kept through process
            $model->set('localfile');
            $model->set('extension');

            $model->set('content', 'label', $this->_('Import text - user header line - separate fields using tabs'),
                    'description', $this->_('Empty fields remove any existing values. Add a field only when used.'),
                    'cols', 120,
                    'elementClass', 'Textarea');

            $this->importModel = $model;
        }

        return $this->importModel;
    }

    /**
     * Display the errors
     *
     * @param MUtil_Model_FormBridge $bridge
     * @param array Errors to display
     */
    protected function displayErrors(MUtil_Model_FormBridge $bridge, $errors = null)
    {
        if (null === $errors) {
            $errors = $this->_errors;
        }

        if ($errors) {
            $element = new MUtil_Form_Element_Html('errors');
            $element->ul($errors, array('class' => $this->errorClass));

            $bridge->addElement($element);
        }
    }

    /**
     * Display a header
     *
     * @param MUtil_Model_FormBridge $bridge
     * @param mixed $header Header content
     * @param string $tagName
     */
    protected function displayHeader(MUtil_Model_FormBridge $bridge, $header, $tagName = 'h2')
    {
        $element = new MUtil_Form_Element_Html('step_header');
        $element->$tagName($header);

        $bridge->addElement($element);
    }

    /**
     * Try to get the current translator
     *
     * @return MUtil_Model_ModelTranslatorInterface or false if none is current
     */
    protected function getImportTranslator()
    {
        if (! (isset($this->formData['trans']) && $this->formData['trans'])) {
            $this->_errors[] = $this->_('No translation definition specified');
            return false;
        }

        if (! isset($this->importTranslators[$this->formData['trans']])) {
            $this->_errors[] = sprintf($this->_('Translation definition %s does not exist.'), $this->formData['trans']);
            return false;
        }

        if (! $this->importTranslators[$this->formData['trans']] instanceof MUtil_Model_ModelTranslatorInterface) {
            $this->_errors[] = sprintf($this->_('%s is not a valid translation definition.'), $this->formData['trans']);
            return false;
        }

        return $this->importTranslators[$this->formData['trans']];
    }

    /**
     * The number of steps in this form
     *
     * @return int
     */
    protected function getStepCount()
    {
        return 5;
    }

    /**
     * Get the descriptions of the translators
     *
     * @return areay key -> description
     */
    protected function getTranslatorDescriptions()
    {
        if (! $this->_translatorDescriptions) {
            $results = array();
            foreach ($this->importTranslators as $key => $translator) {
                if ($translator instanceof MUtil_Model_ModelTranslatorInterface) {
                    $results[$key] = $translator->getDescription();
                }
            }

            asort($results);

            $this->_translatorDescriptions = $results;
        }

        return $this->_translatorDescriptions;
    }

    /**
     * Get the descriptions of the translators
     *
     * @param mixed $for A single translator, an array of translators or all translators if null;
     * @return array key -> description
     */
    protected function getTranslatorTable($for = null)
    {
        if (null === $for) {
            $for = $this->getTranslatorDescriptions();
        } elseif (!is_array($for)) {
            $descriptors = $this->getTranslatorDescriptions();
            if (! isset($descriptors[$for])) {
                throw new Zend_Exception("Unknown translator $for passed to " . __CLASS__ . '->' . __FUNCTION__ . '()');
            }
            $for = array($for => $descriptors[$for]);
        }

        $results = array_fill_keys($this->targetModel->getItemsOrdered(), array());
        $minimal = array(); // Array for making sure all fields are there

        foreach ($for as $transKey => $transName) {
            if (! isset($this->importTranslators[$transKey])) {
                throw new Zend_Exception("Unknown translator $for passed to " . __CLASS__ . '->' . __FUNCTION__ . '()');
            }
            $translator = $this->importTranslators[$transKey];

            if ($translator instanceof MUtil_Model_ModelTranslatorInterface) {

                $translator->setTargetModel($this->targetModel);
                $translations = $translator->getFieldsTranslations();

                $minimal[$transName] = ' ';

                foreach ($translations as $source => $target) {
                    // Skip numeric fields
                    if (! is_int($source)) {
                        $results[$target][$transName] = $source;
                    }
                }
            }
        }

        $output = array();
        foreach ($results as $name => $resultRow) {

            if ($resultRow) {
                if ($this->targetModel->has($name, 'label')) {
                    $label = $this->targetModel->get($name, 'label');
                } else {
                    $label = $name;
                }

                // $field = $this->_targetModel->get($name, 'type', 'maxlength', 'label', 'required');
                switch ($this->targetModel->get($name, 'type')) {
                    case MUtil_Model::TYPE_NOVALUE:
                        unset($results[$name]);
                        continue;;

                    case MUtil_Model::TYPE_NUMERIC:
                        $maxlength = $this->targetModel->get($name, 'maxlength');
                        if ($maxlength) {
                            $decimals = $this->targetModel->get($name, 'decimals');
                            if ($decimals) {
                                $type = sprintf($this->_('A number of length %d, with a precision of %d digits after the period.'), $maxlength, $decimals);
                            } else {
                                $type = sprintf($this->_('A whole number of length %d.'), $maxlength);
                            }
                        } else {
                            $type = $this->_('A numeric value');
                        }
                        break;

                    case MUtil_Model::TYPE_DATE:
                        $type = $this->_('Date value using ISO 8601: yyyy-mm-dd');
                        break;

                    case MUtil_Model::TYPE_DATETIME:
                        $type = $this->_('Datetime value using ISO 8601: yyyy-mm-ddThh:mm::ss[+-hh:mm]');
                        break;

                    case MUtil_Model::TYPE_TIME:
                        $type = $this->_('Time value using ISO 8601: hh:mm::ss[+-hh:mm]');
                        break;

                    default:
                        $maxlength = $this->targetModel->get($name, 'maxlength');
                        if ($maxlength) {
                            $type = sprintf(
                                    $this->plural('Text, %d character', 'Text, %d characters', $maxlength),
                                    $maxlength
                                    );
                        } else {
                            $type = $this->_('Text');
                        }
                        break;

                }
                if ($options = $this->targetModel->get($name, 'multiOptions')) {
                    $cutoff = 8;
                    if (count($options) < $cutoff) {
                        $type .= $this->_('; one of: ') . implode($this->_(', '), array_keys($options));
                    } else {
                        $type .= $this->_('; e.g. one of: ') .
                                implode($this->_(', '), array_slice(array_keys($options), 0, $cutoff - 1)) .
                                $this->_(', ...');
                    }
                }

                $required = $this->targetModel->get($name, 'required');

                $resultRow[$this->_('Field')]    = $label;
                $resultRow[$this->_('Content')]  = $type;
                $resultRow[$this->_('Required')] = $required ? $this->_('Yes') : ' ';

                $output[$name] = $resultRow + $minimal;
            }
        }

        return $output;
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see MUtil_Registry_TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput()
    {
        if ($this->request instanceof MUtil_Controller_Request_Cli) {

            $this->processCli();
            return false;
        }
        return parent::hasHtmlOutput();
    }

    /**
     * Initialize the _items variable to hold all items from the model
     */
    protected function initItems()
    {
        parent::initItems();

        // Remove content as big text slab will slow things down and storage is locally in file
        $i = array_search('content', $this->_items);
        if (false !== $i) {
            unset($this->_items[$i]);
        }
    }

    /**
     * Hook that loads the form data from $_POST or the model
     *
     * Or from whatever other source you specify here.
     */
    protected function loadFormData()
    {
        if ($this->request->isPost()) {
            $this->formData = $this->request->getPost() + $this->formData;
        } else {

            // I assume that if formData is set it is the correct formData
            if (! $this->formData)  {

                // B.t.w. we do not use the model for the initial data
                $this->formData['extension'] = 'txt';
                $this->formData['localfile'] = MUtil_File::createTemporaryIn(
                        $this->tempDirectory,
                        $this->request->getControllerName() . '_'
                        );
                $this->formData['trans']     = $this->defaultImportTranslator;
            }
        }

        // Must always exists
        if (!(isset($this->formData['mode']) && $this->formData['mode'])) {
            $this->formData['mode'] = 'file';
        }
        $this->fileMode = 'file' === $this->formData['mode'];

        // Set the translator
        if (isset($this->formData['trans'], $this->importTranslators[$this->formData['trans']]) &&
                $this->importTranslators[$this->formData['trans']] instanceof MUtil_Model_ModelTranslatorInterface) {

            $this->importer->setImportTranslator($this->importTranslators[$this->formData['trans']]);
        }

        // MUtil_Echo::track($_POST, $_FILES, $this->formData);
    }

    /**
     * (Try to) load the source model
     *
     * @return boolean True if successful
     */
    protected function loadSourceModel()
    {
        try {
            if (! $this->sourceModel) {
                $this->importer->setSourceFile($this->formData['localfile'], $this->formData['extension']);
                $this->sourceModel = $this->importer->getSourceModel();
            }
        } catch (Exception $e) {
            $this->_errors[] = $e->getMessage();
        }

        return $this->sourceModel instanceof MUtil_Model_ModelAbstract;
    }

    /**
     * Code execution in batch mode
     *
     * @return void
     */
    protected function processCli()
    {
        try {
            // Lookup in importTranslators
            $transName = $this->request->getParam('trans', $this->defaultImportTranslator);
            if (! isset($this->importTranslators[$transName])) {
                throw new MUtil_Model_ModelTranslateException(sprintf(
                        $this->_("Unknown translator '%s'. Should be one of: %s"),
                        $transName,
                        implode($this->_(', '), array_keys($this->importTranslators))
                    ));
            }
            $translator = $this->importTranslators[$transName];

            $this->importer->setSourceFile($this->request->getParam('file'));
            $this->importer->setImportTranslator($translator);

            // MUtil_Registry_Source::$verbose = true;
            $batch = $this->importer->getCheckAndImportBatch();
            $batch->setVariable('addImport', !$this->request->getParam('check', false));
            $batch->runContinuous();

            if ($batch->getMessages(false)) {
                echo implode("\n", $batch->getMessages()) . "\n";
            }
            if (! $batch->getCounter('import_errors')) {
                echo sprintf("%d records imported, %d records changed.\n", $batch->getCounter('imported'), $batch->getCounter('changed'));
            }

        } catch (Exception $e) {
            $messages[] = "IMPORT ERROR!";
            $messages[] = $e->getMessage();
            $messages[] = null;
            $messages[] = sprintf(
                    "Usage instruction: %s %s file=filename [trans=[%s]] [check=1]",
                    $this->request->getControllerName(),
                    $this->request->getActionName(),
                    implode('|', array_keys($this->importTranslators))
                    );
            $messages[] = sprintf(
                    "\tRequired parameter: file=filename to import, absolute or relative to %s",
                    getcwd()
                    );
            $messages[] = sprintf(
                    "\tOptional parameter: trans=[%s] default is %s",
                    implode('|', array_keys($this->importTranslators)),
                    $this->defaultImportTranslator
                    );
            $messages[] = "\tOptional parameter: check=[0|1], 0=default, 1=check input only";
            echo implode("\n", $messages) . "\n";
        }
    }

    /**
     * Hook containing the actual save code.
     *
     * Call's afterSave() for user interaction.
     *
     * @see afterSave()
     */
    protected function saveData()
    {
        // do nothing, save occurs in batch
    }

    /**
     * Set what to do when the form is 'finished'.
     *
     * @return MUtil_Snippets_ModelFormSnippetAbstract (continuation pattern)
     */
    protected function setAfterSaveRoute()
    {
        if (isset($this->formData['localfile']) && file_exists($this->formData['localfile'])) {
            // Now is a good moment to remove the temporary file
            @unlink($this->formData['localfile']);
        }

        parent::setAfterSaveRoute();
    }
}
