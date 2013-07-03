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
 * @since      Class available since MUtil version 1.2
 */
class ModelImportSnippet extends MUtil_Snippets_ModelFormSnippetAbstract
{
    /**
     * Array key of the default import translator
     *
     * @var string
     */
    protected $defaultImportTranslator;

    /**
     * Class used
     *
     * @var string
     */
    protected $formatBoxClass;

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
     * Creates the model
     *
     * @return MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        return $this->model;
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
        parent::getHtmlOutput($view);

        $seq = new MUtil_Html_Sequence();

        $seq->h1($this->_('Import data'));

        if (isset($this->formData['trans'], $this->importTranslators[$this->formData['trans']])
                && $this->formData['trans']) {

            $trans = $this->importTranslators[$this->formData['trans']];

            if ($trans instanceof MUtil_Model_ModelTranslatorInterface) {
                $trans->setTargetModel($this->getModel());
                $fieldInfo = $trans->getImportFields();

                $table = MUtil_Html_TableElement::createArray($fieldInfo, $this->_('Import format'), true);
                $table->appendAttrib('class', $this->formatBoxClass);

                $seq->append($table);
            }
        }

        $seq->append($this->_form);

        return $seq;
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
     * Makes sure there is a form.
     */
    protected function loadForm()
    {
        if (! $this->_form) {
            $this->_form = $this->createForm();

            $element = new Zend_Form_Element_Select('trans');
            $element->setLabel($this->_('Translator'))
                    ->setMultiOptions($this->getTranslatorDescriptions())
                    ->setAttrib('size', min(count($this->importTranslators) + 1, 6));
            $this->_form->addElement($element);

            $element = new Zend_Form_Element_File('file');
            $element->setLabel($this->_('Import file'));
            $this->_form->addElement($element);

            $element = new Zend_Form_Element_Textarea('input');
            $element->setLabel($this->_('Text import'))
                    ->setAttrib('cols', 80)
                    ->setAttrib('rows', 20);
            $this->_form->addElement($element);

            $this->saveLabel = $this->_('Check import');
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
            // Assume that if formData is set it is the correct formData
            if (! $this->formData)  {
                $this->formData['trans'] = $this->defaultImportTranslator;
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

        $targetModel = $this->getModel();
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

        // echo count($this->getModel()->saveAll($data)) . "\n";
        // echo $this->getModel()->getChanged() . "\n";
        echo MUtil_Console::removeHtml(MUtil_Echo::out());
        exit();
    }
}
