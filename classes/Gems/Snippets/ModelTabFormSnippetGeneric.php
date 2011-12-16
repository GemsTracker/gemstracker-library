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
 * Short description of file
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: Sample.php 215 2011-07-12 08:52:54Z michiel $
 */

/**
 * Short description for ModelTabFormSnippetGeneric
 *
 * Long description for class ModelTabFormSnippetGeneric (if any)...
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 * @deprecated Class deprecated since version 2.0
 */
class Gems_Snippets_ModelTabFormSnippetGeneric extends Gems_Snippets_ModelFormSnippetGeneric
{
    /**
     *
     * @var Gems_TabForm
     */
    protected $_form;

    /**
     * Array of item names still to be added to the form
     *
     * @var array
     */
    protected $_items;

    /**
     * Add items to the bridge, and remove them from the items array
     *
     * @param MUtil_Model_FormBridge $bridge
     * @param string $element1
     *
     * @return void
     */
    protected function addItems($bridge, $element1)
    {
        $args = func_get_args();
        if (count($args)<2) {
            throw new Gems_Exception_Coding('Use at least 2 arguments, first the bridge and then one or more idividual items');
        }
        
        $bridge   = array_shift($args);
        $elements = $args;

        //Remove the elements from the _items variable
        $this->_items = array_diff($this->_items, $elements);

        //And add them to the bridge
        foreach($elements as $name) {
            if ($label = $this->model->get($name, 'label')) {
                $bridge->add($name);
            } else {
                $bridge->addHidden($name);
            }
        }
    }

    /**
     * Adds elements from the model to the bridge that creates the form.
     *
     * Overrule this function to add different elements to the browse table, without
     * having to recode the core table building code.
     *
     * @param MUtil_Model_FormBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     * @param array $items
     */
    protected function addFormElements(MUtil_Model_FormBridge $bridge, MUtil_Model_ModelAbstract $model, $items = null)
    {
        //Get all elements in the model if not already done
        $this->initItems();

        //Now add all remaining items to the last last tab (if any)
        foreach($this->_items as $name) {
            if ($label = $model->get($name, 'label')) {
                $bridge->add($name);
            } else {
                $bridge->addHidden($name);
            }
        }
    }

    /**
     * Perform some actions on the form, right before it is displayed but already populated
     *
     * Here we add the table display to the form.
     *
     * @return Zend_Form
     */
    public function beforeDisplay()
    {
        //If needed, add a row of link buttons to the bottom of the form
        $form = $this->_form;
        if ($links = $this->getMenuList()) {
            $element = new MUtil_Form_Element_Html('formLinks');
            $element->setValue($links);
            $element->setOrder(999);
            if ($form instanceof Gems_TabForm)  {
                $form->resetContext();
            }
            $form->addElement($element);
            $form->addDisplayGroup(array('formLinks'), 'form_buttons');
        }
    }

    /**
     * Creates an empty form. Allows overruling in sub-classes.
     *
     * @param mixed $options
     * @return Gems_TabForm
     */
    protected function createForm($options = null)
    {
        $form = new Gems_TabForm($options);
        $this->_form = $form;

        //Now first add the saveButton as it needs to be outside the tabs
        $this->addSaveButton();

        return $form;
    }

    /**
     * Initialize the _items variable to hold all items from the model
     */
    protected function initItems()
    {
        if (is_null($this->_items)) {
            $this->_items = $this->model->getItemsOrdered();
        }
    }
}