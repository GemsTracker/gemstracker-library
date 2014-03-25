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
 * @package    MUtil
 * @subpackage Form_Element
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Show a table containing a subform repeated for the number of rows set for
 * this item when rendered.
 *
 * @package    MUtil
 * @subpackage Form_Element
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class MUtil_Form_Element_Table extends Zend_Form_Element_Xhtml implements MUtil_Form_Element_SubFocusInterface
{
    /**
     * Table is an array of values by default
     *
     * @var bool
     */
    protected $_isArray = true;

    /**
     * The model sub form all others are copied from
     *
     * @var Zend_Form
     */
    protected $_subForm;

    /**
     * Actual clones of form
     *
     * @var array of Zend_Form
     */
    protected $_subForms;

    /**
     * Constructor
     *
     * $spec may be:
     * - string: name of element
     * - array: options with which to configure element
     * - Zend_Config: Zend_Config with options for configuring element
     *
     * @param Zend_Form $subForm
     * @param  string|array|Zend_Config $spec
     * @throws Zend_Form_Exception if no element name after initialization
     */
    public function __construct(Zend_Form $subForm, $spec, $options = null)
    {
        $this->setSubForm($subForm);

        parent::__construct($spec, $options);
    }

    /**
     * Get the (possibly focusing) elements/dispalygroups/form contained by this element
     *
     * return array of elements or subforms
     */
    public function getSubFocusElements()
    {
        // If the subforms have been initialezed return them, otherwise return the (later cloned) parent form
        if ($this->_subForms) {
            return $this->_subForms;
        }

        return $this->_subForm;
    }

    /**
     *
     * @return Zend_Form
     */
    public function getSubForm()
    {
        return $this->_subForm;
    }

    /**
     *
     * @return array of Zend_Form
     */
    public function getSubForms()
    {
        return $this->_subForms;
    }

    /**
     * Validate element value
     *
     * If a translation adapter is registered, any error messages will be
     * translated according to the current locale, using the given error code;
     * if no matching translation is found, the original message will be
     * utilized.
     *
     * Note: The *filtered* value is validated.
     *
     * @param  mixed $value
     * @param  mixed $context
     * @return boolean
     */
    public function isValid($value, $context = null)
    {
        $valid = parent::isValid($value, $context);

        // Subforms are set bet setValue() called by parent::isValid()
        if ($this->_subForms) {
            foreach ($value as $id => $data) {
                $valid = $this->_subForms[$id]->isValid($data) && $valid;
            }
        }

        return $valid;
    }

    /**
     * Load default decorators
     *
     * @return void
     */
    public function loadDefaultDecorators()
    {
        if ($this->loadDefaultDecoratorsIsDisabled()) {
            return;
        }

        $decorators = $this->getDecorators();
        if (empty($decorators)) {
            $this->addDecorator('Table', array('vertical' => 0))
                ->addDecorator('HtmlTag', array('tag' => 'dd',
                                                'id'  => $this->getName() . '-element'))
                ->addDecorator('Label', array('tag' => 'dt'));
        }
    }

    /**
     * Change  the sub form later
     *
     * @param Zend_Form $subForm
     * @return \MUtil_Form_Element_Table (continuation pattern)
     */
    public function setSubForm(Zend_Form $subForm)
    {
        $this->_subForm = $subForm;
        return $this;
    }

    /**
     * Set element value
     *
     * @param  mixed $value
     * @return \MUtil_Form_Element_Table (continuation pattern)
     */
    public function setValue($value)
    {
        // $this->setElementsBelongTo($this->getName());
        if ($this->_subForm && $value) {
            $this->_subForm->setElementsBelongTo($this->getName());

            foreach ($value as $id => $row) {

                if (isset($this->_subForms[$id])) {
                    $this->_subForms[$id]->populate($row);

                } else {
                    $subForm = clone $this->_subForm;

                    $name = $this->getName() . '[' . $id . ']';
                    $subForm->setElementsBelongTo($name);
                    $subForm->setName($name);
                    $subForm->populate($row);

                    $this->_subForms[$id] = $subForm;
                }
            }
        }

        return parent::setValue($value);
    }
}