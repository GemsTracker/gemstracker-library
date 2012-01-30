<?php

/**
 * Copyright (c) 2011, Erasmus MC
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
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * The FormBridge contains utility classes to enable the quick construction of
 * a form using a model.
 *
 * @see Zend_Form
 * @see MUtil_Model_ModelAbstract
 *
 * @package    MUtil
 * @subpackage Model
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class MUtil_Model_FormBridge
{
    const AUTO_OPTIONS     = 'auto';
    const CHECK_OPTIONS    = 'check';
    const DATE_OPTIONS     = 'date';
    const DISPLAY_OPTIONS  = 'display';
    const EXHIBIT_OPTIONS  = 'exhibit';
    const FILE_OPTIONS     = 'file';
    const GROUP_OPTIONS    = 'displaygroup';
    const JQUERY_OPTIONS   = 'jquery';
    const MULTI_OPTIONS    = 'multi';
    const PASSWORD_OPTIONS = 'password';
    const TAB_OPTIONS      = 'tab';
    const TEXT_OPTIONS     = 'text';
    const TEXTAREA_OPTIONS = 'textarea';

    /**
     * The key to use in the Zend_Registry to store global fixed options
     */
    const REGISTRY_KEY = 'MUtil_Model_FormBridge';

    protected $form;
    protected $model;

    /**
     * When no size is set for a text-element, the size will be set to the minimum of the
     * maxsize and this value.
     *
     * @var int
     */
    public $defaultSize = 40;

    // First list html attributes, then Zend attributes, lastly own attributes
    private $_allowedOptions = array(
        self::AUTO_OPTIONS     => array('elementClass', 'multiOptions'),
        self::CHECK_OPTIONS    => array('checkedValue', 'uncheckedValue'),
        self::DATE_OPTIONS     => array('dateFormat', 'storageFormat'),
        self::DISPLAY_OPTIONS  => array('accesskey', 'autoInsertNotEmptyValidator', 'class', 'disabled', 'description', 'escape', 'label', 'onclick', 'readonly', 'required', 'tabindex', 'value', 'showLabels'),
        self::EXHIBIT_OPTIONS  => array('formatFunction'),
        self::FILE_OPTIONS     => array('accept', 'count', 'destination', 'valueDisabled'),
        self::GROUP_OPTIONS    => array('elements', 'legend', 'separator'),
        self::JQUERY_OPTIONS   => array('jQueryParams'),
        self::MULTI_OPTIONS    => array('disable', 'multiOptions', 'onchange', 'separator', 'size', 'disableTranslator'),
        self::PASSWORD_OPTIONS => array('repeatLabel'),
        self::TAB_OPTIONS      => array('value'),
        self::TEXT_OPTIONS     => array('maxlength', 'minlength', 'onchange', 'onfocus', 'onselect', 'size'),
        self::TEXTAREA_OPTIONS => array('cols', 'rows', 'wrap'),
        );

    public function __construct(MUtil_Model_ModelAbstract $model, Zend_Form $form)
    {
        $this->model = $model;
        $this->form = $form;

        if (! $form->getName()) {
            $form->setName($model->getName());
        }
    }

    protected function _addToForm($name, Zend_Form_Element $element)
    {
        $this->form->addElement($element);
        $this->_applyValidators($name, $element);

        // MUtil_Echo::r($element->getOrder(), $element->getName());

        return $element;
    }

    protected function _applyValidators($name, Zend_Form_Element $element)
    {
        $validators = $this->model->get($name, 'validators');

        if ($validator = $this->model->get($name, 'validator')) {
            if ($validators) {
                array_unshift($validators, $validator);
            } else {
                $validators = array($validator);
            }
        }

        if ($validators) {
            foreach ($validators as $validator) {
                if (is_array($validator)) {
                    call_user_func_array(array($element, 'addValidator'), $validator);
                } else {
                    $element->addValidator($validator);
                }
            }
        }
    }

    private function _getStringLength(array &$options)
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
                $options['size'] = min($options['maxlength'], $this->defaultSize);
            }
            $stringlength['max'] = $options['maxlength'];
        }

        return isset($stringlength) ? $stringlength : null;
    }

    /**
     * Returns the options from the allowedoptions array, using the supplied options first and trying
     * to find the missing ones in the model.
     *
     * @param string $name
     * @param array $options
     * @param array $allowedOptionKeys_array
     * @return array
     */
    private function _mergeOptions($name, array $options, $allowedOptionKeys_array)
    {
        $args = func_get_args();
        $allowedOptionsKeys = MUtil_Ra::args($args, 2);
        
        $allowedOptions = array();
        foreach ($allowedOptionsKeys as $allowedOptionsKey) {
            if (is_array($allowedOptionsKey)) {
                $allowedOptions = array_merge($allowedOptions, $allowedOptionsKey);
            } else {
                if (array_key_exists($allowedOptionsKey, $this->_allowedOptions)) {
                    $allowedOptions = array_merge($allowedOptions, $this->_allowedOptions[$allowedOptionsKey]);
                } else {
                    $allowedOptions[] = $allowedOptionsKey;
                }
            }
        }

        //If not explicitly set, use the form value for translatorDisabled, since we
        //create the element outside the form scope and later add it
        if (!isset($options['disableTranslator']) && array_search('disableTranslator', $allowedOptions) !== false) {
            $options['disableTranslator'] = $this->form->translatorIsDisabled();
        }

        // Move options to model.
        if (isset($options['validator'])) {
            $this->model->set($name, 'validators[]', $options['validator']);
            unset($options['validator']);
        }

        if ($allowedOptions) {
            // Remove options already filled. Using simple array addition
            // might trigger a lot of lazy calculations that are not needed.

            //First strip the options that are not allowed           
            if (MUtil_Model::$verbose) {
                $strippedKeys = array_keys(array_diff_key($options, array_flip($allowedOptions)));
                if (!empty($strippedKeys)) {
                    MUtil_Echo::r($strippedKeys, 'stripped from options for ' . $name);
                }
            }
            $options = array_intersect_key($options, array_flip($allowedOptions));

            foreach ($allowedOptions as $key => $option) {
                if (array_key_exists($option, $options)) {
                    unset($allowedOptions[$key]);
                }
            }

            if ($allowedOptions) {
                // MUtil_Echo::r($allowedOptions);
                $result = $this->model->get($name, $allowedOptions);
                return (array) $result + (array) $options;
            }
        }

        return $options;
    }

    private function _moveOption($name, array &$options, $default = null)
    {
        if (isset($options[$name])) {
            $result = $options[$name];
            unset($options[$name]);
            return $result;
        }

        return $default;
    }

    public function add($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $options = func_get_args();
        $options = MUtil_Ra::pairs($options, 1);

        $options = $this->_mergeOptions($name, $options, self::AUTO_OPTIONS);

        if (isset($options['elementClass'])) {
            $method = 'add' . $options['elementClass'];
            unset($options['elementClass']);

        } else {
            if (isset($options['multiOptions'])) {
                $method = 'addSelect';
            } else {
                $method = 'addText';
            }
        }

        return $this->$method($name, $options);
    }

    public function addCheckbox($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $options = func_get_args();
        $options = MUtil_Ra::pairs($options, 1);

        // Is often set for browse table, but should not be used here,
        // while the default ->add function does add it.
        $this->_moveOption('multiOptions', $options);

        $options = $this->_mergeOptions($name, $options,
            self::DISPLAY_OPTIONS, self::CHECK_OPTIONS);

        self::applyFixedOptions(__FUNCTION__, $options);

        $element = new Zend_Form_Element_Checkbox($name, $options);

        return $this->_addToForm($name, $element);
    }

    public function addDate($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $options = func_get_args();
        $options = MUtil_Ra::pairs($options, 1);

        $options = $this->_mergeOptions($name, $options,
            self::DATE_OPTIONS, self::DISPLAY_OPTIONS, self::JQUERY_OPTIONS, self::TEXT_OPTIONS);

        $elementName = $name;

        // Allow centrally set options
        self::applyFixedOptions(__FUNCTION__, $options);

        if (isset($options['dateFormat'])) {
            // Make sure the model knows the dateFormat (can be important for storage).
            $this->getModel()->set($name, 'dateFormat', $options['dateFormat']);
        }

        $element = new MUtil_JQuery_Form_Element_DatePicker($elementName, $options);

        return $this->_addToForm($name, $element);
    }

    /**
     * Adds a displayGroup to the bridge
     *
     * Use a description to set a label for the group. All elements should be added to the bridge before adding
     * them to the group. Use the special option showLabels to display the labels of the individual fields
     * in front of them. This option is only available in tabbed forms, to display multiple fields in one tablecell.
     *
     * Without labels:
     * usage: $this->addDisplayGroup('mygroup', array('element1', 'element2'), 'description', 'Pretty name for the group');
     *
     * With labels:
     * usage: $this->addDisplayGroup('mygroup', array('element1', 'element2'), 'description', 'Pretty name for the group', 'showLabels', true);
     *
     * Or specify using the 'elements' option:
     * usage: $this->addDisplayGroup('mygroup', array('elements', array('element1', 'element2'), 'description', 'Pretty name for the group'));
     *
     * @param string $name Name of element
     * @param array $elements or MUtil_Ra::pairs() name => value array with 'elements' item in it
     * @param mixed $arrayOrKey1 MUtil_Ra::pairs() name => value array
     * @return Zend_Form_Displaygroup
     */
    public function addDisplayGroup($name, $elements, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $options = func_get_args();
        $options = MUtil_Ra::pairs($options, 2);

        //MUtil_Echo::track($elements);
        if (isset($elements['elements'])) {
            MUtil_Echo::track($elements, $options);
            $tmpElements = $elements['elements'];
            unset($elements['elements']);
            $options = $elements + $options;
            $elements = $tmpElements;
            //MUtil_Echo::track($elements, $options);
        }

        $options = $this->_mergeOptions($name, $options,
            self::DISPLAY_OPTIONS, self::MULTI_OPTIONS);

        $this->form->addDisplayGroup($elements, $name, $options);

        return $this->form->getDisplayGroup($name);
    }

    public function addElement(Zend_Form_Element $element)
    {
        return $this->_addToForm($element->getName(), $element);
    }

    public function addExhibitor($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $options = func_get_args();
        $options = MUtil_Ra::pairs($options, 1);

        $options = $this->_mergeOptions($name, $options,
            self::DATE_OPTIONS, self::DISPLAY_OPTIONS, self::EXHIBIT_OPTIONS, self::MULTI_OPTIONS);

        $element = new MUtil_Form_Element_Exhibitor($name, $options);

        $this->form->addElement($element);
        // MUtil_Echo::r($element->getOrder(), $element->getName());

        return $element;
    }

    public function addFile($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $options = func_get_args();
        $options = MUtil_Ra::pairs($options, 1);

        $options = $this->_mergeOptions($name, $options,
            self::DISPLAY_OPTIONS, self::FILE_OPTIONS, self::TEXT_OPTIONS);

        $filename  = $this->_moveOption('filename',  $options);
        $count     = $this->_moveOption('count',     $options);
        $size      = $this->_moveOption('size',      $options);
        $extension = $this->_moveOption('extension', $options);

        $element = new Zend_Form_Element_File($name, $options);

        if ($filename) {
            $count = 1;
            // When
            // 1) an extension filter was defined,
            // 2) it concerns a single extension and
            // 3) $filenane does not have an extension
            // then add the extension to the name.
            if ($extension &&
                (false === strpos($extension, ',')) &&
                (false === strpos($name, '.'))) {
                $filename .= '.' . $extension;
            }
            $element->addFilter(new Zend_Filter_File_Rename(array('target' => $filename, 'overwrite' => true)));
        }
        if ($count) {
            $element->addValidator('Count', false, $count);
        }
        if ($size) {
            $element->addValidator('Size', false, $size);
        }
        if ($extension) {
            $element->addValidator('Extension', false, $extension);
        }

        return $this->_addToForm($name, $element);
    }

    public function addFilter($name, $filter, $options = array())
    {
        $element = $this->form->getElement($name);
        $element->addFilter($filter, $options);

        return $this;
    }

    public function addHidden($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $options = func_get_args();
        $options = MUtil_Ra::pairs($options, 1);

        $element = new Zend_Form_Element_Hidden($name, $options);

        $this->form->addElement($element);

        return $element;
    }

    public function addHiddenMulti($name_args)
    {
        $args = MUtil_Ra::args(func_get_args());

        foreach ($args as $name) {
            $this->addHidden($name);
        }
    }

    public function addHtml($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $options = func_get_args();
        $options = MUtil_Ra::pairs($options, 1);

        $options = $this->_mergeOptions($name, $options,
            self::DISPLAY_OPTIONS);

        $element = new MUtil_Form_Element_Html($name, $options);

        $this->form->addElement($element);

        return $element;
    }

    public function addList($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $options = func_get_args();
        $options = MUtil_Ra::pairs($options, 1);

        // Is often added automatically, but should not be used here
        $this->_moveOption('maxlength', $options);

        $options = $this->_mergeOptions($name, $options,
            self::DISPLAY_OPTIONS, self::MULTI_OPTIONS);

        if (! array_key_exists('size', $options)) {
            $count = count($options['multiOptions']);
            $options['size'] = $count > 5 ? 5 : $count + 1;
        }

        $element = new Zend_Form_Element_Select($name, $options);

        return $this->_addToForm($name, $element);
    }

    public function addPassword($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $options = func_get_args();
        $options = MUtil_Ra::pairs($options, 1);

        $options = $this->_mergeOptions($name, $options,
            self::DISPLAY_OPTIONS, self::PASSWORD_OPTIONS, self::TEXT_OPTIONS);

        $stringlength = $this->_getStringLength($options);

        if ($repeatLabel = $this->_moveOption('repeatLabel', $options)) {
            $repeatOptions = $options;

            $this->_moveOption('description', $repeatOptions);

            $repeatOptions['label'] = $repeatLabel;
            $repeatName = $name . '__repeat';
        }

        $element = new Zend_Form_Element_Password($name, $options);
        $this->_applyValidators($name, $element);
        $this->form->addElement($element);

        if ($stringlength) {
            $element->addValidator('StringLength', true, $stringlength);
        }

        if (isset($repeatLabel)) {
            $repeatElement = new Zend_Form_Element_Password($repeatName, $repeatOptions);
            $this->form->addElement($repeatElement);

            if ($stringlength) {
                $repeatElement->addValidator('StringLength', true, $stringlength);
            }

            $element->addValidator(new MUtil_Validate_IsConfirmed($repeatName, $repeatLabel));
            $repeatElement->addValidator(new MUtil_Validate_IsConfirmed($name, isset($options['label']) ? $options['label'] : null));
        }

        return $element;
    }

    public function addRadio($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $options = func_get_args();
        $options = MUtil_Ra::pairs($options, 1);

        $options = $this->_mergeOptions($name, $options,
            self::DISPLAY_OPTIONS, self::MULTI_OPTIONS);

        $element = new Zend_Form_Element_Radio($name, $options);

        return $this->_addToForm($name, $element);
        // $this->form->addDisplayGroup(array($name), $name . '__group', array('Legend' => 'Hi'));
        // return $element;
    }

    public function addSelect($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $options = func_get_args();
        $options = MUtil_Ra::pairs($options, 1);

        // Is often added automatically, but should not be used here
        $this->_moveOption('maxlength', $options);

        $options = $this->_mergeOptions($name, $options,
            self::DISPLAY_OPTIONS, self::MULTI_OPTIONS);

        $element = new Zend_Form_Element_Select($name, $options);

        return $this->_addToForm($name, $element);
    }


    /**
     * Adds a group of checkboxes (multicheckbox)
     *
     * @see Zend_Form_Element_MultiCheckbox
     *
     * @param string $name Name of element
     * @param mixed $arrayOrKey1 MUtil_Ra::pairs() name => value array
     * @return Zend_Form_Element_MultiCheckbox
     */
    public function addMultiCheckbox($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $options = func_get_args();
        $options = MUtil_Ra::pairs($options, 1);

        // Is often added automatically, but should not be used here
        $this->_moveOption('maxlength', $options);

        $options = $this->_mergeOptions($name, $options,
            self::DISPLAY_OPTIONS, self::MULTI_OPTIONS);

        $element = new Zend_Form_Element_MultiCheckbox($name, $options);

        return $this->_addToForm($name, $element);
    }

    /**
     * Adds a select box with multiple options
     *
     * @see Zend_Form_Element_Multiselect
     *
     * @param string $name Name of element
     * @param mixed $arrayOrKey1 MUtil_Ra::pairs() name => value array
     */
    public function addMultiSelect($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $options = func_get_args();
        $options = MUtil_Ra::pairs($options, 1);

        // Is often added automatically, but should not be used here
        $this->_moveOption('maxlength', $options);

        $options = $this->_mergeOptions($name, $options,
            self::DISPLAY_OPTIONS, self::MULTI_OPTIONS);

        $element = new Zend_Form_Element_Multiselect($name, $options);

        return $this->_addToForm($name, $element);
    }

    /**
     * Start a tab after this element, with the given name / title
     *
     * Can ofcourse only be used in tabbed forms.
     *
     * Usage:
     * <code>
     * $this->addTab('tab1')->h3('First tab');
     * </code>
     * or
     * <code>
     * $this->addTab('tab1', 'value', 'First tab');
     * </code>
     *
     * @param string $name Name of element
     * @param mixed $arrayOrKey1 MUtil_Ra::pairs() name => value array
     * @return MUtil_Form_Element_Tab
     */
    public function addTab($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $options = func_get_args();
        $options = MUtil_Ra::pairs($options, 1);

        $options = $this->_mergeOptions($name, $options,
            self::DISPLAY_OPTIONS, self::TAB_OPTIONS);

        if (method_exists($this->form, 'addTab')) {
            return $this->form->addTab($name, isset($options['value']) ? $options['value'] : null);
        } else {
            $element = new MUtil_Form_Element_Tab($name, $options);
            $this->form->addElement($element);
        }

        return $element;
    }

    public function addText($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $options = func_get_args();
        $options = MUtil_Ra::pairs($options, 1);

        $options = $this->_mergeOptions($name, $options,
            self::DISPLAY_OPTIONS, self::TEXT_OPTIONS);

        $stringlength = $this->_getStringLength($options);

        $element = new Zend_Form_Element_Text($name, $options);

        if ($stringlength) {
            $element->addValidator('StringLength', true, $stringlength);
        }

        return $this->_addToForm($name, $element);
    }

    public function addTextarea($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $options = func_get_args();
        $options = MUtil_Ra::pairs($options, 1);

        $options = $this->_mergeOptions($name, $options,
            self::DISPLAY_OPTIONS, self::TEXT_OPTIONS, self::TEXTAREA_OPTIONS);

        $stringlength = $this->_getStringLength($options);
        // Remove as size and maxlength are not used for textarea's
        unset($options['size'], $options['maxlength']);

        $element = new Zend_Form_Element_Textarea($name, $options);

        if ($stringlength) {
            $element->addValidator('StringLength', true, $stringlength);
        }

        return $this->_addToForm($name, $element);
    }

    /**
     *
     * @param sting $elementName
     * @param mixed $validator
     * @param boolean $breakChainOnFailure
     * @param mixed $options
     * @return MUtil_Model_FormBridge
     */
    public function addValidator($elementName, $validator, $breakChainOnFailure = false, $options = array())
    {
        $element = $this->form->getElement($elementName);
        $element->addValidator($validator, $breakChainOnFailure, $options);

        return $this;
    }

    public static function applyFixedOptions($type, array &$options)
    {
        static $typeOptions;

        if (! $typeOptions) {
            if (Zend_Registry::isRegistered(self::REGISTRY_KEY)) {
                $typeOptions = Zend_Registry::get(self::REGISTRY_KEY);
            } else {
                $typeOptions = array();
            }
        }

        if (substr($type, 0, 3) == 'add') {
            $type = strtolower(substr($type, 3));
        }
        // MUtil_Echo::rs($type, $options);

        if (isset($typeOptions[$type])) {
            foreach ($typeOptions[$type] as $key => $value) {
                if (is_array($value) && isset($options[$key])) {
                    $options[$key] = $value + $options[$key];
                } else {
                    $options[$key] = $value;
                }
            }
        }
        // MUtil_Echo::rs('After', $options, $typeOptions);
    }

    /**
     *
     * @return Zend_Form
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     *
     * @return MUtil_Model_ModelAbstract
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Retrieve a tab from a Gems_TabForm to add extra content to it
     *
     * @param string $name
     * @return Gems_Form_TabSubForm
     */
    public function getTab($name)
    {
        if (method_exists($this->form, 'getTab')) {
            return $this->form->getTab($name);
        }
    }
}
