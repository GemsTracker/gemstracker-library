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
abstract class MUtil_Model_Processor_ElementProcessorAbstract implements MUtil_Model_ProcessorInterface
{
    /**
     * When no size is set for a text-element, the size will be set to the minimum of the
     * maxsize and this value.
     *
     * @var int
     */
    protected $defaultTextLength = 40;

    /**
     * Allow use of general display options
     *
     * @var boolean
     */
    protected $useDisplayOptions = true;

    /**
     * Allow use of answers select specific options
     *
     * @var boolean
     */
    protected $useMultiOptions = false;

    /**
     * Allow use of textbox specific options
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
    protected function applyElement(MUtil_Model_Input $input, Zend_Form_Element $element)
    {
        if ($value = $input->getOutput()) {
            $element->setValue($value);
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
}
