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
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Displays an edit form using tabs based on the model the model set through the $model snippet parameter.
 *
 * This class is not in the standard snippet loading directories and does not follow
 * their naming conventions, but exists only to make it simple to extend this class
 * for a specific implementation.
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_Snippets_ModelTabFormSnippetGeneric extends Gems_Snippets_ModelFormSnippetGeneric
{
    /**
     *
     * @var Gems_TabForm
     */
    protected $_form;

    /**
     * Adds elements from the model to the bridge that creates the form.
     *
     * Overrule this function to add different elements to the browse table, without
     * having to recode the core table building code.
     *
     * @param MUtil_Model_FormBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     */
    protected function addFormElements(MUtil_Model_FormBridge $bridge, MUtil_Model_ModelAbstract $model)
    {
        //Get all elements in the model if not already done
        $this->initItems();

        // Add 'tooltip' to the allowed displayoptions
        $displayOptions = $bridge->getAllowedOptions(MUtil_Model_FormBridge::DISPLAY_OPTIONS);
        if (!array_search('tooltip', $displayOptions)) {
            $displayOptions[] = 'tooltip';
        }
        $bridge->setAllowedOptions(MUtil_Model_FormBridge::DISPLAY_OPTIONS, $displayOptions);

        $tab   = 0;
        $group = 0;
        foreach ($model->getItemsOrdered() as $name) {
            // Get all options at once
            $modelOptions = $model->get($name);
            if ($tabName = $model->get($name, 'tab')) {
                $bridge->addTab('tab' . $tab, 'value', $tabName);
                $tab++;
            }

            if ($model->has($name, 'label')) {
                $bridge->add($name);
                
                if ($theName = $model->get('startGroup')) {
                    //We start a new group here!
                    $groupElements   = array();
                    $groupElements[] = $name;
                    $groupName       = $theName;
                } elseif ($theName = $model->get('endGroup')) {
                    //Ok, last element define the group
                    $groupElements[] = $name;
                    $bridge->addDisplayGroup('grp_' . $groupElements[0], $groupElements,
                            'description', $groupName,
                            'showLabels', ($theName == 'showLabels'),
                            'class', 'grp' . $group);
                    $group++;
                    unset($groupElements);
                    unset($groupName);
                } else {
                    //If we are in a group, add the elements to the group
                    if (isset($groupElements)) {
                        $groupElements[] = $name;
                    }
                }
            } else {
                $bridge->addHidden($name);
            }
        }
    }

    /**
     * Simple default function for making sure there is a $this->_saveButton.
     *
     * As the save button is not part of the model - but of the interface - it
     * does deserve it's own function.
     */
    protected function addSaveButton()
    {
        $this->_form->resetContext();
        parent::addSaveButton();
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
}