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
 * @version    $Id: Table.php 345 2011-07-28 08:39:24Z 175780 $
 * @package    MUtil
 * @subpackage Form_Element
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

/**
 * @package    MUtil
 * @subpackage Form_Element
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */
class MUtil_Form_Element_Table extends Zend_Form_Element_Xhtml implements MUtil_Form_Element_SubFocusInterface
{
    /**
     * Use formSelect view helper by default
     * @var string
     */
    public $helper = '';


    /**
     * Multiselect is an array of values by default
     * @var bool
     */
    protected $_isArray = true;

    protected $_subForm;
    protected $_subForms;

    /**
     * Constructor
     *
     * $spec may be:
     * - string: name of element
     * - array: options with which to configure element
     * - Zend_Config: Zend_Config with options for configuring element
     *
     * @param  string|array|Zend_Config $spec
     * @return void
     * @throws Zend_Form_Exception if no element name after initialization
     */
    public function __construct(Zend_Form $subForm, $spec, $options = null)
    {
        $this->setSubForm($subForm);

        parent::__construct($spec, $options);
    }

    public function getSubFocusElements()
    {
        return $this->getSubForms();
    }

    public function getSubForm()
    {
        return $this->_subForm;
    }

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

    public function setSubForm(Zend_Form $subForm)
    {
        $this->_subForm = $subForm;
        return $this;
    }

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