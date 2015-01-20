<?php

/**
 * Copyright (c) 2014, Erasmus MC
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
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    MUtil
 * @subpackage Model_Bridge
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id: FormBridge.php $
 */

/**
 * The FormBridge contains utility classes to enable the quick construction of
 * a form using a model.
 *
 * @see Zend_Form
 * @see MUtil_Model_ModelAbstract
 *
 * @package    MUtil
 * @subpackage Model_Bridge
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.4 8-mei-2014 11:01:08
 */
class MUtil_Model_Bridge_FormBridge implements MUtil_Model_Bridge_FormBridgeInterface
{
    const AUTO_OPTIONS       = 'auto';
    const CHECK_OPTIONS      = 'check';
    const DATE_OPTIONS       = 'date';
    const DISPLAY_OPTIONS    = 'display';
    const EXHIBIT_OPTIONS    = 'exhibit';
    const FAKESUBMIT_OPTIONS = 'fakesubmit';
    const FILE_OPTIONS       = 'file';
    const GROUP_OPTIONS      = 'displaygroup';
    const JQUERY_OPTIONS     = 'jquery';
    const MULTI_OPTIONS      = 'multi';
    const PASSWORD_OPTIONS   = 'password';
    const SUBFORM_OPTIONS    = 'subform';
    const TAB_OPTIONS        = 'tab';
    const TEXT_OPTIONS       = 'text';
    const TEXTAREA_OPTIONS   = 'textarea';

    /**
     * The key to use in the Zend_Registry to store global fixed options
     */
    const REGISTRY_KEY = 'MUtil_Model_Bridge_FormBridge';

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
        self::AUTO_OPTIONS       => array('elementClass', 'multiOptions'),
        self::CHECK_OPTIONS      => array('checkedValue', 'uncheckedValue'),
        self::DATE_OPTIONS       => array('dateFormat', 'storageFormat'),
        self::DISPLAY_OPTIONS    => array('accesskey', 'autoInsertNotEmptyValidator', 'class', 'disabled', 'description', 'escape', 'escapeDescription', 'label', 'onclick', 'readonly', 'required', 'tabindex', 'value', 'showLabels', 'labelplacement'),
        self::EXHIBIT_OPTIONS    => array('formatFunction', 'itemDisplay'),
        self::FAKESUBMIT_OPTIONS => array('label', 'tabindex', 'disabled'),
        self::FILE_OPTIONS       => array('accept', 'count', 'destination', 'extension', 'filename', 'valueDisabled'),
        self::GROUP_OPTIONS      => array('elements', 'legend', 'separator'),
        self::JQUERY_OPTIONS     => array('jQueryParams'),
        self::MULTI_OPTIONS      => array('disable', 'multiOptions', 'onchange', 'separator', 'size', 'disableTranslator'),
        self::PASSWORD_OPTIONS   => array('renderPassword', 'repeatLabel'),
        self::SUBFORM_OPTIONS    => array('class', 'escape', 'form', 'tabindex'),
        self::TAB_OPTIONS        => array('value'),
        self::TEXT_OPTIONS       => array('maxlength', 'minlength', 'onblur', 'onchange', 'onfocus', 'onselect', 'size'),
        self::TEXTAREA_OPTIONS   => array('cols', 'rows', 'wrap', 'decorators'),
        );

    /**
     * Construct the bridge while setting the model.
     *
     * Extra parameters can be added in subclasses, but the first parameter
     * must remain the model.
     *
     * @param MUtil_Model_ModelAbstract $model
     * @param Zend_Form $form Rquired
     */
    public function __construct(MUtil_Model_ModelAbstract $model, Zend_Form $form = null)
    {
        $this->model = $model;
        $this->form  = $form;

        if (! $form instanceof Zend_Form) {
            throw new MUtil_Model_ModelException(
                    "No form specified while create a form bridge for model " . $model->getName()
                    );
        }

        if (! $form->getName()) {
            $form->setName($model->getName());
        }
    }

    /**
     * Add the element to the form and apply any filters & validators
     *
     * @param string $name
     * @param Zend_Form_Element $element
     * @return Zend_Form_Element
     */
    protected function _addToForm($name, $element, $options = null)
    {
        $this->form->addElement($element, $name, $options);
        if (is_string($element)) {
            $element = $this->form->getElement($name);
        }
        if (isset($options['escapeDescription']))  {
            $description = $element->getDecorator('Description');
            if ($description instanceof \Zend_Form_Decorator_Description) {
                $description->setEscape($options['escapeDescription']);
            }
        }
        $this->_applyFilters($name, $element);
        if (! $element instanceof Zend_Form_Element_Hidden) {
            $this->_applyValidators($name, $element);
        }

        // MUtil_Echo::r($element->getOrder(), $element->getName());

        return $element;
    }

    /**
     * Apply the filters for element $name to the element
     *
     * @param string $name
     * @param Zend_Form_Element $element
     */
    protected function _applyFilters($name, Zend_Form_Element $element)
    {
        $filters = $this->model->get($name, 'filters');

        if ($filter = $this->model->get($name, 'filter')) {
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
     * @param string $name
     * @param Zend_Form_Element $element
     */
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
            $element->addValidators($validators);
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
            $allowedOptionsFlipped = array_flip($allowedOptions);

            // First strip the options that are not allowed
            if (MUtil_Model::$verbose) {
                $strippedKeys = array_keys(array_diff_key($options, $allowedOptionsFlipped));
                if (!empty($strippedKeys)) {
                    MUtil_Echo::r($strippedKeys, 'stripped from options for ' . $name);
                }
            }
            $options = array_intersect_key($options, $allowedOptionsFlipped);

            // Now get allowed options from the model
            $modelOptions = $this->model->get($name, $allowedOptions);

            // Merge them: first use supplied $options, and add missing values from model
            return (array) $options + (array) $modelOptions;
        }
        return $options;
    }

    /**
     * Find $name in the $options array and unset it. If not found, return the $default value
     *
     * @param string $name
     * @param array $options
     * @param mixed $default
     * @return mixed
     */
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

        /**
         * As this method redirects to the correct 'add' method, we preserve the original options
         * while trying to find the needed ones in the model
         */
        $options = $options + $this->_mergeOptions($name, $options, self::AUTO_OPTIONS);

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

    public function addColorPicker($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $options = func_get_args();
        $options = MUtil_Ra::pairs($options, 1);

        $options = $this->_mergeOptions($name, $options,
            self::DISPLAY_OPTIONS);


        return $this->_addToForm($name, 'ColorPicker' , $options);
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

        return $this->_addToForm($name, 'Checkbox', $options);
    }

    /**
     * Add a ZendX date picker to the form
     *
     * @param string $name Name of element
     * @param mixed $arrayOrKey1 MUtil_Ra::pairs() name => value array
     * @return ZendX_JQuery_Form_Element_DatePicker
     */
    public function addDate($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $options = func_get_args();
        $options = MUtil_Ra::pairs($options, 1);

        $options = $this->_mergeOptions($name, $options,
            self::DATE_OPTIONS, self::DISPLAY_OPTIONS, self::JQUERY_OPTIONS, self::TEXT_OPTIONS);

        // Allow centrally set options
        $type = __FUNCTION__;
        if (isset($options['dateFormat'])) {
            list($dateFormat, $separator, $timeFormat) = MUtil_Date_Format::splitDateTimeFormat($options['dateFormat']);

            if ($timeFormat) {
                if ($dateFormat) {
                    $type = 'datetime';
                } else {
                    $type = 'time';
                }
            }
        }
        self::applyFixedOptions($type, $options);

        if (isset($options['dateFormat'])) {
            // Make sure the model knows the dateFormat (can be important for storage).
            $this->getModel()->set($name, 'dateFormat', $options['dateFormat']);
        }

        // Make sure form knows it is a jQuery form
        $this->form->activateJQuery();

        return $this->_addToForm($name, 'DatePicker', $options);
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

        // MUtil_Echo::track($elements);
        if (isset($elements['elements'])) {
            // MUtil_Echo::track($elements, $options);
            $tmpElements = $elements['elements'];
            unset($elements['elements']);
            $options = $elements + $options;
            $elements = $tmpElements;
            // MUtil_Echo::track($elements, $options);
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

    /**
     * Add an element that just displays the value to the user
     *
     * @param string $name Name of element
     * @param mixed $arrayOrKey1 MUtil_Ra::pairs() name => value array
     * @return \MUtil_Form_Element_Exhibitor
     */
    public function addExhibitor($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $options = $this->_mergeOptions(
                $name,
                MUtil_Ra::pairs(func_get_args(), 1),
                self::DATE_OPTIONS,
                self::DISPLAY_OPTIONS,
                self::EXHIBIT_OPTIONS,
                self::MULTI_OPTIONS
                );

        $element = $this->form->createElement('exhibitor', $name, $options);

        $this->form->addElement($element);
        // MUtil_Echo::r($element->getOrder(), $element->getName());

        return $element;
    }

    /**
     * Add an element that just displays the value to the user
     *
     * @param string $name Name of element
     * @param mixed $arrayOrKey1 MUtil_Ra::pairs() name => value array
     * @return \MUtil_Form_Element_FakeSubmit
     */
    public function addFakeSubmit($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $options = $this->_mergeOptions(
                $name,
                MUtil_Ra::pairs(func_get_args(), 1),
                self::FAKESUBMIT_OPTIONS
                );

        return $this->_addToForm($name, 'fakeSubmit', $options);
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
            // 3) $filename does not have an extension
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
            // Now set a custom validation message telling what extensions are allowed
            $validator = $element->getValidator('Extension');
            $validator->setMessage('Only %extension% files are accepted.', Zend_Validate_File_Extension::FALSE_EXTENSION);
        }

        return $this->_addToForm($name, $element);
    }

    public function addFilter($name, $filter, $options = array())
    {
        $element = $this->form->getElement($name);
        $element->addFilter($filter, $options);

        return $this;
    }

    /**
     * Adds a form multiple times in a table
     *
     * You can add your own 'form' either to the model or here in the parameters.
     * Otherwise a form of the same class as the parent form will be created.
     *
     * All elements not yet added to the form are added using a new FormBridge
     * instance using the default label / non-label distinction.
     *
     * @param string $name Name of element
     * @param mixed $arrayOrKey1 MUtil_Ra::pairs() name => value array
     * @return MUtil_Form_Element_Table
     */
    public function addFormTable($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $options = func_get_args();
        $options = MUtil_Ra::pairs($options, 1);

        $options = $this->_mergeOptions($name, $options,
            self::SUBFORM_OPTIONS);

        if (isset($options['form'])) {
            $form = $options['form'];
            unset($options['form']);
        } else {
            $formClass = get_class($this->form);
            $form = new $formClass();
        }

        $submodel = $this->model->get($name, 'model');
        if ($submodel instanceof MUtil_Model_ModelAbstract) {
            $bridge = new self($submodel, $form);

            foreach ($submodel->getItemsOrdered() as $itemName) {
                if (! $form->getElement($itemName)) {
                    if ($submodel->has($itemName, 'label') || $submodel->has($itemName, 'elementClass')) {
                        $bridge->add($itemName);
                    } else {
                        $bridge->addHidden($itemName);
                    }
                }
            }
        }

        $element = new MUtil_Form_Element_Table($form, $name, $options);

        $this->form->addElement($element);

        return $element;
    }

    public function addHidden($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $options = func_get_args();
        $options = MUtil_Ra::pairs($options, 1);


        return $this->_addToForm($name, 'Hidden', $options);
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

        return $this->_addToForm($name, 'html', $options);
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

        return $this->_addToForm($name, 'Select', $options);
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

        return $this->_addToForm($name, 'MultiCheckbox', $options);
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

        return $this->_addToForm($name, 'Multiselect', $options);
    }

    /**
     * Stub for elements where no class should be displayed.
     *
     * @param string $name Name of element
     */
    public function addNone($name)
    {
        return null;
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

        $element = $this->form->createElement('password', $name, $options);
        $this->_applyFilters($name, $element);
        $this->_applyValidators($name, $element);
        $this->form->addElement($element);

        if ($stringlength) {
            $element->addValidator('StringLength', true, $stringlength);
        }

        if (isset($repeatLabel)) {
            $repeatElement = $this->form->createElement('password', $repeatName, $repeatOptions);
            $this->form->addElement($repeatElement);
            $this->_applyFilters($name, $repeatElement);

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

        return $this->_addToForm($name, 'Radio', $options);
    }

    public function addSelect($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $options = func_get_args();
        $options = MUtil_Ra::pairs($options, 1);

        // Is often added automatically, but should not be used here
        $this->_moveOption('maxlength', $options);

        $options = $this->_mergeOptions($name, $options,
            self::DISPLAY_OPTIONS, self::MULTI_OPTIONS);

        return $this->_addToForm($name, 'Select', $options);
    }

    /**
     * Adds a form multiple times in a table
     *
     * You can add your own 'form' either to the model or here in the parameters.
     * Otherwise a form of the same class as the parent form will be created.
     *
     * All elements not yet added to the form are added using a new FormBridge
     * instance using the default label / non-label distinction.
     *
     * @param string $name Name of element
     * @param mixed $arrayOrKey1 MUtil_Ra::pairs() name => value array
     * @return MUtil_Form_Element_Table
     */
    public function addSubForm($name, $arrayOrKey1 = null, $value1 = null, $key2 = null, $value2 = null)
    {
        $options = func_get_args();
        $options = MUtil_Ra::pairs($options, 1);

        $options = $this->_mergeOptions($name, $options,
            self::SUBFORM_OPTIONS);

        if (isset($options['form'])) {
            $form = $options['form'];
            unset($options['form']);
        } else {
            $formClass = get_class($this->form);
            $form = new $formClass();
        }

        $submodel = $this->model->get($name, 'model');
        if ($submodel instanceof MUtil_Model_ModelAbstract) {
            $bridge = new self($submodel, $form);

            foreach ($submodel->getItemsOrdered() as $itemName) {
                if (! $form->getElement($itemName)) {
                    if ($submodel->has($itemName, 'label') || $submodel->has($itemName, 'elementClass')) {
                        $bridge->add($itemName);
                    } else {
                        $bridge->addHidden($itemName);
                    }
                }
            }
        }

        $element = new MUtil_Form_Element_SubForms($form, $name, $options);

        $this->form->addElement($element);

        return $element;
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
        $options = $this->_mergeOptions(
                $name,
                MUtil_Ra::pairs(func_get_args(), 1),
                self::DISPLAY_OPTIONS,
                self::TEXT_OPTIONS
                );

        $stringlength = $this->_getStringLength($options);

        if ($stringlength) {
            $this->model->set($name, 'validators[]', array('StringLength', true, $stringlength));
        }

        return $this->_addToForm($name, 'Text', $options);
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

        if ($stringlength) {
            $this->model->set($name, 'validators[]', array('StringLength', true, $stringlength));
        }

        return $this->_addToForm($name, 'Textarea', $options);
    }

    /**
     *
     * @param sting $elementName
     * @param mixed $validator
     * @param boolean $breakChainOnFailure
     * @param mixed $options
     * @return MUtil_Model_Bridge_FormBridge
     */
    public function addValidator($elementName, $validator, $breakChainOnFailure = false, $options = array())
    {
        $element = $this->form->getElement($elementName);
        $element->addValidator($validator, $breakChainOnFailure, $options);

        return $this;
    }

    /**
     * Get the fixed options set in the registry. These settings overrule any
     * options set through the model or as parameters.
     *
     * @staticvar array $typeOptions
     * @param string $type
     * @param array  $options Existing options
     */
    public static function applyFixedOptions($type, array &$options)
    {
        if (Zend_Registry::getInstance()->isRegistered(self::REGISTRY_KEY)) {
            $typeOptions = Zend_Registry::get(self::REGISTRY_KEY);
        } else {
            $typeOptions = array();
        }

        if (substr($type, 0, 3) == 'add') {
            $type = strtolower(substr($type, 3));
        }
        // MUtil_Echo::rs($type, $options);

        if (isset($typeOptions[$type])) {
            foreach ($typeOptions[$type] as $key => $value) {
                if (is_array($value) && isset($options[$key])) {
                    $options[$key] = $options[$key] + $value;

                } else {
                    $options[$key] = $value;
                }
            }
        }
        // MUtil_Echo::rs('After', $options, $typeOptions);
    }

    /**
     * Returns the allowed options for a certain key or all options if no
     * key specified
     *
     * @param string $key
     * @return array
     */
    public function getAllowedOptions($key = null)
    {
        if (is_null($key)) {
            return $this->_allowedOptions;
        }

        if (array_key_exists($key, $this->_allowedOptions)) {
            return $this->_allowedOptions[$key];
        } else {
            return array();
        }
    }

    /**
     * Get a single fixed option set in the registry.
     *
     * @param string $type   Type name [add]Function of this object
     * @param string $option Option name
     */
    public static function getFixedOption($type, $option)
    {
        $options = array();

        self::applyFixedOptions($type, $options);

        if (isset($options[$option])) {
            return $options[$option];
        }

        return null;
    }

    /**
     * Get a single fixed option set in the registry.
     *
     * @param string $type Type name [add]Function of this object
     */
    public static function getFixedOptions($type)
    {
        $options = array();

        self::applyFixedOptions($type, $options);

        return $options;
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

    /**
     * Set the allowed options for a certain key to the specified options array
     *
     * @param string $key
     * @param array $options
     * @return MUtil_Model_Bridge_FormBridge
     */
    public function setAllowedOptions($key, $options)
    {
        if (is_string($options)) {
            $options = array($options);
        }

        $this->_allowedOptions[$key] = $options;
        return $this;
    }

    /**
     * Set fixed options in the registry.
     *
     * @param array $options The options type => array(options
     */
    public static function setFixedOptions($options)
    {
        Zend_Registry::set(MUtil_Model_Bridge_FormBridge::REGISTRY_KEY, $options);
    }
}