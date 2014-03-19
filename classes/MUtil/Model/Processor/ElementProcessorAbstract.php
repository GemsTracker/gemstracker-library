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
 * @version    $Id: ElementProcessorAbstract.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 *
 *
 * @package    MUtil
 * @subpackage Model
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.2
 */
abstract class MUtil_Model_Processor_ElementProcessorAbstract extends MUtil_Translate_TranslateableAbstract
    implements MUtil_Model_Processor_ElementProcessorInterface
{
    /**
     * When true, the item is a new item
     *
     * @var boolean
     */
    protected $createData = false;

    /**
     * When no size is set for a text-element, the size will be set to the minimum of the
     * maxsize and this value.
     *
     * @var int
     */
    protected $defaultTextLength = 40;

    /**
     *
     * @var Zend_Form
     */
    protected $form;

    /**
     * Allow use of general display options,
     * when using this objects options functions
     *
     * @var boolean
     */
    protected $useDisplayOptions = true;

    /**
     * Allow use of answers select specific options,
     * when using this objects options functions
     *
     * @var boolean
     */
    protected $useMultiOptions = false;

    /**
     * Allow use of textbox specific options,
     * when using this objects options functions
     *
     * @var boolean
     */
    protected $useTextOptions = false;

    /**
     * Apply the standard processing needed and set the element as the output
     *
     * @param MUtil_Model_Input $input
     * @param Zend_Form_Element $element
     */
    protected function _applyElement(MUtil_Model_Input $input, Zend_Form_Element $element)
    {
        $value = $input->getOutput();

        if (! $value instanceof Zend_Form_Element) {
            $element->setValue($value);
        }

        /* $this->form->addElement($element, $name, $options);
        if (is_string($element)) {
            $element = $this->form->getElement($name);
        }
        // */
        $this->_applyFilters($input, $element);
        if (! $element instanceof Zend_Form_Element_Hidden) {
            $this->_applyValidators($input, $element);
        }

        $input->setOutput($element);
    }

    /**
     * Apply the filters for element $name to the element
     *
     * @param MUtil_Model_Input $input
     * @param Zend_Form_Element $element
     */
    protected function _applyFilters(MUtil_Model_Input $input, Zend_Form_Element $element)
    {
        $filters = $input->getOption('filters');

        if ($filter = $input->getOption('filter')) {
            if ($filters) {
                array_unshift($filters, $filter);
            } else {
                $filters = array($filter);
            }
        }

        if ($filters) {
            foreach ($filters as $filter) {
                if (is_array($filter)) {
                    call_user_func_array(array($element, 'addFilter'), $filter);
                } else {
                    $element->addFilter($filter);
                }
            }
        }
    }

    /**
     * Apply the validators for element $name to the element
     *
     * @param MUtil_Model_Input $input
     * @param Zend_Form_Element $element
     */
    protected function _applyValidators(MUtil_Model_Input $input, Zend_Form_Element $element)
    {
        $validators = $input->getOption('validators');

        if ($validator = $input->getOption('validator')) {
            if ($validators) {
                array_unshift($validators, $validator);
            } else {
                $validators = array($validator);
            }
        }

        if ($validators) {
            $element->addValidators($validators);
        }
    }

    /**
     * Apply the standard processing needed and set the element as the output
     *
     * @param MUtil_Model_Input $input
     * @param Zend_Form_Element $element
     */
    protected function _createElement(MUtil_Model_Input $input, $elementClass, $options)
    {
        if (! $this->hasForm()) {
            throw new MUtil_Model_Assembler_AssemblerException('Cannot use createElement without a form.');
        }
        if (! is_string($elementClass)) {
            throw new MUtil_Model_Assembler_AssemblerException('The elementClass must be a string.');
        }
        $this->form->addElement($elementClass, $input->getName(), $options);
        $element = $this->form->getElement($input->getName());

        $value = $input->getOutput();

        if (! $value instanceof Zend_Form_Element) {
            $element->setValue($value);
        }

        $this->_applyFilters($input, $element);
        if (! $element instanceof Zend_Form_Element_Hidden) {
            $this->_applyValidators($input, $element);
        }

        $input->setOutput($element);
    }

    /**
     * Nested array of allowed option names
     *
     * @return array
     */
    protected function getAllowedOptionsNames()
    {
        $options = array();

        if ($this->useDisplayOptions) {
            $options[] = array(
                'accesskey',
                'autoInsertNotEmptyValidator',
                'class',
                'disabled',
                'description',
                'escape',
                'label',
                'onclick',
                'readonly',
                'required',
                'tabindex',
                'value',
                'showLabels',
                'labelplacement',
                );
        }

        if ($this->useMultiOptions) {
            $options[] = array(
                'disable',
                'multiOptions',
                'onchange',
                'separator',
                'size',
                'disableTranslator'
                );

        }

        if ($this->useTextOptions) {
            $options[] = array(
                'maxlength',
                'minlength',
                'onchange',
                'onfocus',
                'onselect',
                'size');
        }

        /*
        self::CHECK_OPTIONS    => array('checkedValue', 'uncheckedValue'),
        self::DATE_OPTIONS     => array('dateFormat', 'storageFormat'),
        self::EXHIBIT_OPTIONS  => array('formatFunction'),
        self::FILE_OPTIONS     => array('accept', 'count', 'destination', 'valueDisabled'),
        self::GROUP_OPTIONS    => array('elements', 'legend', 'separator'),
        self::JQUERY_OPTIONS   => array('jQueryParams'),
        self::PASSWORD_OPTIONS => array('repeatLabel'),
        self::TAB_OPTIONS      => array('value'),
        self::TEXTAREA_OPTIONS => array('cols', 'rows', 'wrap'),
        //*/

        return $options;
    }

    /**
     * Get the form - if known
     *
     * @return Zend_Form or null
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * Get those options that are actually allowed to be used by this element
     *
     * @param MUtil_Model_Input $input
     * @return array
     */
    protected function getFilteredOptions(MUtil_Model_Input $input)
    {
        $allowedOptions = MUtil_Ra::flatten($this->getAllowedOptionsNames());

        $options = $input->getOptions($allowedOptions);

        return array_intersect_key($options, array_flip($allowedOptions));
    }

    /**
     * Is the form set
     *
     * @return boolean
     */
    public function hasForm()
    {
        return $this->form instanceof Zend_Form;
    }

    /**
     * When true we're editing a new item
     *
     * @return boolean
     */
    public function isCreatingData()
    {
        return $this->createData;
    }

    /**
     * Create a StringLength validator (array) and remove those
     * options that should not appear in the output.
     *
     * @param array $options
     * @return array For validator creation
     */
    protected function getStringLengthValidator(array &$options)
    {
        if (isset($options['minlength'])) {
            $stringlength['min'] = $options['minlength'];
            unset($options['minlength']);
        }
        if (isset($options['size']) && (! isset($options['maxlength']))) {
            $options['maxlength'] = $options['size'];
        }
        if (isset($options['maxlength'])) {
            if (! isset($options['size'])) {
                $options['size'] = min($options['maxlength'], $this->defaultTextLength);
            }
            $stringlength['max'] = $options['maxlength'];
        }

        if (isset($stringlength)) {
            return array('StringLength', true, $stringlength);
        }
    }

    /**
     * When true we're editing a new item
     *
     * @param boolean $isNew
     * @return \MUtil_Model_Processor_ElementProcessorAbstract (Continuatiuon pattern)
     */
    public function setCreatingData($isNew = true)
    {
        $this->createData = $isNew;
        return $this;
    }

    /**
     * Set the form - if known
     *
     * @param Zend_Form $form
     * @return \MUtil_Model_Processor_ElementProcessorAbstract (Continuatiuon pattern)
     */
    public function setForm(Zend_Form $form)
    {
        $this->form = $form;
        return $this;
    }
}
