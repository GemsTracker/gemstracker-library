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
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $id: ModelTranslatorAbstract.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 * Translators can translate the data from one model to be saved using another
 * model.
 *
 * @package    MUtil
 * @subpackage Model
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.3
 */
abstract class MUtil_Model_ModelTranslatorAbstract extends MUtil_Translate_TranslateableAbstract
    implements MUtil_Model_ModelTranslatorInterface
{
    /**
     * A description that enables users to choose the transformer they need.
     *
     * @var string
     */
    protected $_description;

    /**
     *
     * @var array
     */
    protected $_errors = array();

    /**
     * The source of the data
     *
     * @var MUtil_Model_ModelAbstract
     */
    protected $_sourceModel;

    /**
     * The target of the data
     *
     * @var MUtil_Model_ModelAbstract
     */
    protected $_targetModel;

    /**
     * The string value used for NULL values
     *
     * @var string Uppercase string
     */
    public $nullValue = 'NULL';

    /**
     * The form used to validate the input values
     *
     * @var Zend_Form
     */
    public $targetForm;

    /**
     *
     * @param string $description A description that enables users to choose the transformer they need.
     */
    public function __construct($description = '')
    {
        $this->setDescription($description);
    }

    /**
     *
     * @return array
     */
    protected function _getFilters()
    {
        $filters = array();
        foreach ($this->_targetModel->getCol('filter') as $name => $filter) {
            $filters[$name] = $filter;
        }

        return array_merge_recursive(
                $filters,
                $this->_targetModel->getCol('filters')
                );
    }

    /**
     * Create an empty form for filtering and validation
     *
     * @return \MUtil_Form
     */
    protected function _createTargetForm()
    {
        return new MUtil_Form();
    }

    /**
     * Create a form for filtering and validation, populating it
     * with elements.
     *
     * @return \MUtil_Form
     */
    protected function _makeTargetForm()
    {
        $form   = $this->_createTargetForm();
        $form->setTranslator($this->translate);

        $bridge = new MUtil_Model_FormBridge($this->_targetModel, $form);

        foreach($this->getFieldsTranslations() as $sourceName => $targetName) {
            if ($this->_targetModel->get($targetName, 'label')) {
                $bridge->add($targetName);
            } else {
                $bridge->addHidden($targetName);
            }
        }

        return $form;
    }

    /**
     * Translate textual null values to actual PHP nulls
     *
     * @param mixed $value
     * @return mixed
     */
    public function filterNull($value)
    {
        if ($this->nullValue === strtoupper($value)) {
            return null;
        }

        return $value;
    }

    /**
     * Returns a description of the translator to enable users to choose
     * the translator they need.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->_description;
    }

    /**
     * Returns a description of the tranlator to enable users to choose
     * the transformer the need.
     *
     * @return array of String messages
     */
    public function getErrors()
    {
        $errorOutput = array();
        foreach ($this->_errors as $row => $rowErrors) {
            $start = sprintf($this->_('Row %s'), $row);
            foreach ((array) $rowErrors as $field1 => $errors) {
                if (is_numeric($field1)) {
                    $middle = '';
                } else {
                    $middle = sprintf($this->_(' field %s'), $field1);
                }
                $middle =  $middle . $this->_(': ');
                foreach ((array) $errors as $field2 => $error) {
                    $errorOutput[] = $start . $middle . $error;
                }
            }
        }
        return $errorOutput;
    }

    /**
     * Get the source model, where the data is coming from.
     *
     * @return MUtil_Model_ModelAbstract $sourceModel The source of the data
     */
    public function getSourceModel()
    {
        return $this->_sourceModel;
    }

    /**
     * Get a form for filtering and validation, populating it
     * with elements.
     *
     * @return \Zend_Form
     */
    public function getTargetForm()
    {
        if (! $this->targetForm instanceof Zend_Form) {
            $this->setTargetForm($this->_makeTargetForm());
        }

        return $this->targetForm;
    }

    /**
     * Get the target model, where the data is going to.
     *
     * @return MUtil_Model_ModelAbstract $sourceModel The target of the data
     */
    public function getTargetModel()
    {
        return $this->_targetModel;
    }

    /**
     * True when the transformation generated errors.
     *
     * @return boolean True when there are errora
     */
    public function hasErrors()
    {
        return (boolean) $this->_errors;
    }

    /**
     * Set the description.
     *
     * @param string $description A description that enables users to choose the transformer they need.
     * @return \Gems_Model_ModelTranslatorAbstract (continuation pattern)
     */
    public function setDescription($description)
    {
        $this->_description = $description;
        return $this;
    }

    /**
     * Set the source model, where the data is coming from.
     *
     * @param MUtil_Model_ModelAbstract $sourceModel The source of the data
     * @return \MUtil_Model_ModelTranslatorAbstract (continuation pattern)
     */
    public function setSourceModel(MUtil_Model_ModelAbstract $sourceModel)
    {
        $this->_sourceModel = $sourceModel;
        return $this;
    }

    /**
     * Set a form populated with elements for filtering and validation of
     * the input elements
     *
     * @param Zend_Form $form
     * @return \MUtil_Model_ModelTranslatorAbstract (continuation pattern)
     */
    public function setTargetForm(Zend_Form $form)
    {
        $this->targetForm = $form;
        return $this;
    }

    /**
     * Set the target model, where the data is going to.
     *
     * @param MUtil_Model_ModelAbstract $sourceModel The target of the data
     * @return \MUtil_Model_ModelTranslatorAbstract (continuation pattern)
     */
    public function setTargetModel(MUtil_Model_ModelAbstract $targetModel)
    {
        $this->_targetModel = $targetModel;
        return $this;
    }

    /**
     * Set the translator to use.
     *
     * @param Zend_Translate $translate
     * @return \MUtil_Model_ModelTranslatorAbstract (continuation pattern)
     */
    public function setTranslator(Zend_Translate $translate)
    {
        $this->translate = $translate;
        $this->initTranslateable();

        return $this;
    }

    /**
     * Perform all the translations in the data set.
     *
     * This code does not validate the individual inputs, but does check the ovrall structure of the input
     *
     * @param array $data a nested data set as loaded from the source model
     * @return mixed Nested row array or false when errors occurred
     */
    public function translateImport(array $data)
    {
        // Clear errors
        $this->_errors = array();

        $defaults    = $this->_targetModel->getCol('default');
        $fieldMap    = $this->getFieldsTranslations();
        $fieldKeys   = array_keys($fieldMap);
        $mapRequired = $fieldKeys !== array_values($fieldMap);
        $results     = array();

        $defaults = array_intersect_key($defaults, array_flip($fieldMap));

        // Make sure the target form is set (unless overruled bu child class)
        $this->getTargetForm();

        foreach($data as $key => $row) {
            if ($row instanceof Traversable) {
                $row = iterator_to_array($row);

                // print_r($row);
            }

            if (! (is_array($row) && $row)) {
                // Do not bother with non array data
                continue;
            }

            $rowMap = array_intersect($fieldKeys, array_keys($row));
            if (! $rowMap) {
                $this->_errors[$key][] = $this->_("No field overlap between source and target");
                return false;
            }

            if ($mapRequired) {
                // This does keep the original values. That is intentional.
                foreach ($rowMap as $source) {
                    $row[$fieldMap[$source]] = $row[$source];
                }
            }
            $row = $row + $defaults;

            $row = $this->translateRowValues($row, $key);

            if ($row && $this->targetForm instanceof Zend_Form) {
                $row = $this->validateRowValues($row, $key);
            }

            if ($row) {
                $results[$key] = $row;
            }
        }
        return $results;
    }

    /**
     * Perform any translations necessary for the code to work
     *
     * @param array $row
     * @param scalar $key
     * @return mixed Row array or false when errors occurred
     */
    protected function translateRowValues($row, $key)
    {
        if ($this->nullValue) {
            $row = array_map(array($this, 'filterNull'), $row);
        }

        return $row;
    }

    /**
     * Validate the data against the target form
     *
     * @param array $row
     * @param scalar $key
     * @return mixed Row array or false when errors occurred
     */
    protected function validateRowValues($row, $key)
    {
        // Clean up lingering values
        $this->targetForm->clearErrorMessages()
                ->populate(array());

        if (! $this->targetForm->isValid($row)) {
            $messages = $this->targetForm->getMessages(null, true);
            if (isset($this->_errors[$key])) {
                $this->_errors[$key] = array_merge($this->_errors[$key], $messages);
            } else {
                $this->_errors[$key] = $messages;
            }
            return false;
        }

        $this->targetForm->populate($row);

        $row = $this->targetForm->getValues() + $row;

        return $row;
    }
}
