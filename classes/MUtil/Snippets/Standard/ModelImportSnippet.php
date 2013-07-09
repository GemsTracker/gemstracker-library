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
 *
 *
 * @package    MUtil
 * @subpackage Snippets
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.3
 */
class ModelImportSnippet extends MUtil_Snippets_WizardFormSnippetAbstract
{
    /**
     * Array key of the default import translator
     *
     * @var string
     */
    protected $defaultImportTranslator;

    /**
     * Class for import fields table
     *
     * @var string
     */
    protected $formatBoxClass;

    /**
     *
     * @var MUtil_Model_ModelAbstract
     */
    protected $importModel;

    /**
     *
     * @var array of MUtil_Model_ModelTranslatorInterface objects
     */
    protected $importTranslators;

    /**
     *
     * @var MUtil_Model_ModelAbstract
     */
    protected $model;

    /**
     *
     * @var MUtil_Model_ModelAbstract
     */
    protected $targetModel;

    /**
     * Add the elements from the model to the bridge for the current step
     *
     * @param MUtil_Model_FormBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     * @param int $step The current step
     */
    protected function addStepElementsFor(MUtil_Model_FormBridge $bridge, MUtil_Model_ModelAbstract $model, $step)
    {
        $element = new MUtil_Form_Element_Html('step_header');
        $element->h1(sprintf($this->_('Data import, step %d of %d.'), $step, $this->getStepCount()));

        $bridge->addElement($element);

        switch ($step) {
            case 0:
            case 1:
                $this->addStep1($bridge, $model);
                break;

            case 2:
                $this->addStep2($bridge, $model);
                break;

            default:
        }
    }

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
        if (isset($this->formData['mode']) && ('file' == $this->formData['mode'])) {
            $this->addItems($bridge, 'file');
        } else {
            $this->addItems($bridge, 'content');
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

        if ($this->model instanceof MUtil_Model_ModelAbstract) {
            if (! $this->targetModel instanceof MUtil_Model_ModelAbstract) {
                $this->targetModel = $this->model;
            }
        }

        // Cleanup any references to model to avoid confusion
        $this->model = null;
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

            $model->set('trans', 'label', $this->_('Choose a translator'),
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
                    'elementClass', 'File');

            $model->set('content', 'label', $this->_('Import text'),
                    'elementClass', 'Textarea');

            $this->importModel = $model;
        }

        return $this->importModel;
    }

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param Zend_View_Abstract $view Just in case it is needed here
     * @return MUtil_Html_HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(Zend_View_Abstract $view)
    {
        $form = parent::getHtmlOutput($view);

        if (1 == $this->currentStep) {

            $fieldInfo = $this->getTranslatorTable();

            $table = MUtil_Html_TableElement::createArray($fieldInfo, $this->_('Import fields'), true);
            $table->appendAttrib('class', $this->formatBoxClass);

            $element = new MUtil_Form_Element_Html('transtable');
            $element->setValue($table);

            $this->_form->addElement($element);
        }

        return $form;
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
        $results = array();
        foreach ($this->importTranslators as $key => $translator) {
            if ($translator instanceof MUtil_Model_ModelTranslatorInterface) {
                $results[$key] = $translator->getDescription();
            }
        }

        return $results;
    }

    /**
     * Get the descriptions of the translators
     *
     * @return array key -> description
     */
    protected function getTranslatorTable()
    {
        $results = array_fill_keys($this->targetModel->getItemsOrdered(), array());
        $minimal = array(); // Array for making sure all fields are there

        foreach ($this->importTranslators as $transKey => $translator) {

            if ($translator instanceof MUtil_Model_ModelTranslatorInterface) {

                $translator->setTargetModel($this->targetModel);
                $transName    = $translator->getDescription();
                $translations = $translator->getFieldsTranslations();

                $minimal[$transName] = $this->_(' ');

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
                            $type = sprintf($this->_('Text, %d characters'), $maxlength);
                        } else {
                            $type = $this->_('Text');
                        }
                        break;

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
    }

    protected function processCli()
    {
        $messages = array();

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
}
