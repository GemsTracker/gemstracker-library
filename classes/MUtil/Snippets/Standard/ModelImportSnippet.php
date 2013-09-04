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
     * Nested array, caching the output of loadImportData()
     *
     * @var array
     */
    private $_data;

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
    private $importer;

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
        if (isset($this->importTranslators[$this->formData['trans']])) {
            $translator = $this->importTranslators[$this->formData['trans']];

            if ($translator instanceof MUtil_Model_ModelTranslatorInterface) {
                $element = new MUtil_Form_Element_Html('trans_header');
                $element->append($this->_('Choosen translation definition: '));
                $element->strong($translator->getDescription());
                $element->setDecorators(array('Tooltip', 'ViewHelper'));
                $bridge->addElement($element);
            }
        } else {
           $translator = null;
        }

        if ($this->fileMode) {
            $this->addItems($bridge, 'file');
        } else {
            // Add a default content if empty
            if ((!(isset($this->formData['content']) && $this->formData['content'])) &&
                    ($translator instanceof MUtil_Model_ModelTranslatorInterface)) {

                $translator->setTargetModel($this->targetModel);
                $fields = array_filter(array_keys($translator->getFieldsTranslations()), 'is_string');

                $this->formData['content'] = implode("\t", $fields) . "\n" .
                    str_repeat("\t", count($fields) - 1) . "\n";
            }

            $this->addItems($bridge, 'content');
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
            $repeater = $this->sourceModel->loadRepeatable();
            $table    = new MUtil_Html_TableElement($repeater, array('class' => $this->formatBoxClass));

            foreach ($this->sourceModel->getItemsOrdered() as $name) {
                $table->addColumn($repeater->$name, $name);
            }

            $element->setValue($table);
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
        $this->loadImportData();

        if ($this->_errors) {
            $this->displayErrors($bridge);

            $this->nextDisabled = true;
        } else {
            $this->displayErrors($bridge, $this->_('Import data valid. Click Finish for actual import.'));

            $element    = new MUtil_Form_Element_Html('importdisplay');
            $repeater   = new MUtil_Lazy_Repeatable($this->_data);
            $table      = new MUtil_Html_TableElement($repeater, array('class' => $this->formatBoxClass));
            $translator = $this->getImportTranslator();

            foreach ($translator->getFieldsTranslations() as $name) {
                $table->addColumn($repeater->$name, $name);
            }

            $element->setValue($table);
            $bridge->addElement($element);
        }
        // $this->_form->addV
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

            default:
                $this->addStep4($bridge, $model);
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
        }

        if (! $this->targetModel instanceof MUtil_Model_ModelAbstract) {
            if ($this->model instanceof MUtil_Model_ModelAbstract) {
                $this->targetModel = $this->model;
            }
        }

        if ($this->targetModel instanceof MUtil_Model_ModelAbstract) {
            $this->importer->setTargetModel($this->targetModel);
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
        return 4;
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
            return true;
        }
        return parent::hasHtmlOutput();
    }

    /**
     * Is the import OK
     *
     * @return boolean
     */
    public function isImportOk()
    {
        $this->loadImportData();
        // MUtil_Echo::track('passed validator: ' . count($this->_errors));

        return !$this->_errors;
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
            // Assume that if formData is set it is the correct formData
            if (! $this->formData)  {
                $this->formData['trans'] = $this->defaultImportTranslator;
                $this->formData['mode']  = 'file';
            }
        }

        // Must always exists
        if (!(isset($this->formData['mode']) && $this->formData['mode'])) {
            $this->formData['mode'] = 'file';
        }
        $this->fileMode = 'file' === $this->formData['mode'];
    }

    /**
     * Try to import the data.
     *
     * Will set $this->_errors on failure.
     *
     * @return array or false on some errors
     */
    protected function loadImportData()
    {
        if (! $this->_data) {
            $translator = $this->getImportTranslator();

            if (! ($translator && $this->loadSourceModel())) {
                return false;
            }

            $translator->setTargetModel($this->targetModel);
            $translator->setSourceModel($this->sourceModel);

            $data = $this->sourceModel->load();
            $data = $translator->translateImport($data);

            if ($translator->hasErrors()) {
                $this->_errors = array_merge($this->_errors, $translator->getErrors());
            }

            $this->_data = $data;
        }

        return $this->_data;
    }

    /**
     * (Try to) load the source model
     *
     * @return boolean True if successful
     */
    protected function loadSourceModel()
    {
        try {
            if (isset($this->_forms[2])) {
                $localFile = MUtil_File::getTemporaryIn($this->tempDirectory, $this->request->getControllerName() . '_');

                if ($this->fileMode) {
                    $fileElement = $this->_forms[2]->getElement('file');
                    if ($fileElement instanceof Zend_Form_Element_File) {

                        // Now add the rename filter, we did not know the name earlier
                        $fileElement->addFilter(
                                new Zend_Filter_File_Rename(array('target' => $localFile, 'overwrite' => true))
                                );
                        $extension = pathinfo($fileElement->getFileName(), PATHINFO_EXTENSION);

                        if (!$fileElement->receive()) {
                            $this->_errors[] = sprintf(
                                    $this->_("Error retrieving file '%s'."),
                                    $fileElement->getFileName()
                                    );
                        }
                    } else {
                        $this->_errors[] = sprintf(
                                $this->_("File element is of wrong Zend element type '%s'."),
                                get_class($fileElement)
                                );
                    }
                } else {
                    if (isset($this->formData['content']) && $this->formData['content']) {
                        $this->sourceModel = new MUtil_Model_NestedArrayModel('manual input', $this->formData['content']);
                        $extension = 'txt'; // Default extension
                        file_put_contents($localFile, $this->formData['content']);
                    } else {
                        $this->_errors[] = $this->_('No content passed for import.');
                    }
                }

                $this->formData['extension'] = $extension;
                $this->formData['localfile'] = $localFile;
            }

            if (! ($this->_errors || $this->sourceModel)) {
                if ('txt' === $this->formData['extension']) {
                    $this->sourceModel = new MUtil_Model_TabbedTextModel($this->formData['localfile']);
                } elseif ('xml' === $this->formData['extension']) {
                    $this->sourceModel = new MUtil_Model_XmlModel($this->formData['localfile']);
                } else {
                    $this->_errors[] = sprintf(
                            $this->_("Unsupported file extension: %s. Import not possible."),
                            $this->formData['extension']
                            );
                }
            }
        } catch (Exception $e) {
            $this->_errors[] = $e->getMessage();
        }

        return $this->sourceModel instanceof MUtil_Model_ModelAbstract;
    }

    protected function processCli()
    {
        $messages = array();

        /*
        try {
            $this->importer->setSourceFile($this->request->getParam('file'));
            $this->importer->setImportTranslator($this->request->getParam('trans', $this->defaultImportTranslator));

        } catch (Exception $e) {
            $messages[] = "IMPORT ERROR!";
            $messages[] = $e->getMessage();
            $messages[] = null;
            $messages[] = sprintf(
                    "Usage instruction: %s %s file=filename [trans=[%s]]",
                    $this->request->getControllerName(),
                    $this->request->getActionName(),
                    implode('|', array_keys($this->importTranslators))
                    );
            $messages[] = sprintf(
                    "\tRequired parameter: file = filename to import, absolute or relative to %s",
                    getcwd()
                    );
            $messages[] = sprintf(
                    "\tOptional parameter: trans = [%s] default is %s",
                    implode('|', array_keys($this->importTranslators)),
                    $this->defaultImportTranslator
                    );
            echo implode("\n", $messages) . "\n";
            exit();
        }
        exit();
        // */
        $file = $this->request->getParam('file');
        if (! $file) {
            $messages[] = "Missing required parameter: file = filename to import";
        } elseif (!file_exists($file)) {
            if (file_exists(GEMS_ROOT_DIR . DIRECTORY_SEPARATOR . $file)) {
                $file = GEMS_ROOT_DIR . DIRECTORY_SEPARATOR . $file;
            } else {
                $messages[] = "Error in parameter file. '$file' does not exist";
            }
        }

        $transName = $this->request->getParam('trans', $this->defaultImportTranslator);
        if (! isset($this->importTranslators[$transName])) {
            $messages[] = "Unknown value for parameter: trans. Should be one of: " .
                    implode(', ', array_keys($this->importTranslators));
        } elseif ($messages) {
            $messages[] = "Parameter trans defaults to " . $this->defaultImportTranslator .
                    " and can be one of: " . implode(', ', array_keys($this->importTranslators));
        }

        if ($messages) {
            echo implode("\n", $messages);
            exit();
        }

        $trans = $this->importTranslators[$transName];
        if (! $trans instanceof MUtil_Model_ModelTranslatorInterface) {
            $messages[] = "Programming error: Translator $trans does not result in a translator model.";
        }

        if ($messages) {
            echo implode("\n", $messages);
            exit();
        }

        $targetModel = $this->targetModel;
        $trans->setTargetModel($targetModel);

        $ext = pathinfo($file, PATHINFO_EXTENSION);
        if ('txt' === $ext) {
            $sourceModel = new MUtil_Model_TabbedTextModel($file);
        } elseif ('xml' === $ext) {
            // echo $targetModel->getName() . "\n";
            $sourceModel = new MUtil_Model_XmlModel($file);
        } else {
            echo "Unsupported file extension. Import not possible.\n";
        }

        $trans->setSourceModel($sourceModel);

        $data = $sourceModel->load();
        $data = $trans->translateImport($data);

        if ($trans->hasErrors()) {
            echo implode("\n", $trans->getErrors()) . "\n";
            // exit();
        }

        $fields = $trans->getTargetModel()->getItemNames();
        $fields = array_combine($fields, $fields);

        if (is_array($data)) {
            $row = reset($data);
            if (is_array($row)) {
                echo implode("\t", array_intersect(array_keys(reset($data)), $fields)) . "\n";
            }
            foreach ($data as $row) {
                if (is_array($row)) {
                    echo implode("\t", array_intersect_key($row, $fields)) . "\n";
                } else {
                    echo $row . "\n";
                }

            }
        } else {
            echo "No output data.\n";
        }
        // print_r($data);

        // echo count($targetModel->saveAll($data)) . "\n";
        // echo $targetModel->getChanged() . "\n";
        echo MUtil_Console::removeHtml(MUtil_Echo::out());
        exit();
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
        $this->loadImportData();

        if (! $this->_errors) {
            // Signal file copy
            $this->_saved = true;

            $this->targetModel->saveAll($this->_data);

            $changed = $this->targetModel->getChanged();

            $this->addMessage(sprintf($this->_('Imported %d %s'), $changed, $this->getTopic($changed)));
        }
    }

    /**
     * Set what to do when the form is 'finished'.
     *
     * @return MUtil_Snippets_ModelFormSnippetAbstract (continuation pattern)
     */
    protected function setAfterSaveRoute()
    {
        if (isset($this->formData['localfile']) && file_exists($this->formData['localfile'])) {
            $moveTo = false;

            if ((4 == $this->currentStep) && $this->longtermFilename) {
                $this->loadImportData();
                if ($this->_errors && $this->failureDirectory ) {
                    MUtil_File::ensureDir($this->failureDirectory);
                    $moveTo = $this->failureDirectory . DIRECTORY_SEPARATOR . $this->longtermFilename;

                } elseif ($this->_saved && $this->successDirectory) {
                    MUtil_File::ensureDir($this->successDirectory);
                    $moveTo = $this->successDirectory . DIRECTORY_SEPARATOR . $this->longtermFilename;
                }
            }

            if ($moveTo) {
                // Make sure the extension is added
                if (isset($this->formData['extension']) && $this->formData['extension']) {
                    if (!MUtil_String::endsWith($moveTo, '.' . $this->formData['extension'])) {
                        $moveTo = $moveTo . '.' . $this->formData['extension'];
                    }
                }
                // Copy to long term storage location
                if (! copy($this->formData['localfile'], $moveTo)) {
                    throw new MUtil_Model_ModelException(sprintf(
                            $this->_('Copy from %s to %s failed: %s.'),
                            $this->formData['localfile'],
                            $moveTo,
                            MUtil_Error::getLastPhpErrorMessage('reason unknown')
                            ));
                }
            }
            @unlink($this->formData['localfile']);
        }

        return parent::setAfterSaveRoute();
    }
}
