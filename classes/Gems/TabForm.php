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
 * @version    $Id$
 * @package    Gems
 * @subpackage Form
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

/**
 * @package    Gems
 * @subpackage Form
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */
class Gems_TabForm extends Gems_Form
{
    /**
     * Create tabs from MUtil_Form_Element_Tab elements
     *
     * All elements following an element of type MUtil_Form_Element_Tab will be in tabs
     * For these items a subform will be created of the type Gems_Form_TabSubForm
     *
     * @param Zend_Form $form   The form containting the elements
     */
    public static function htmlElementsToTabs($form) {
        foreach ($form as $element) {
            //Make sure error decorator is the last one! (not really needed inside the tabs, but just to make sure)
            $error = $element->getDecorator('Errors');
            if ($error instanceof Zend_Form_Decorator_Errors) {
                $element->removeDecorator('Errors');
                $element->addDecorator($error);
            }
            switch (get_class($element)) {
                case 'MUtil_Form_Element_Tab':
                    //Start a new tab
                    if (isset($tab)) {
                        //First output the old data
                        $tabs[$tab->getName()] = $tab;
                    }
                    $name = $element->getName();
                    $title = $element->getValue();
                    if ($title instanceof MUtil_Html_Sequence) $title = $title->render($form->getView());
                    $tab = new Gems_Form_TabSubForm(array('name' => $name, 'title' => strip_tags($title)));
                    $remove[] = $element->getName();
                    break;

                case 'Zend_Form_Element_Hidden':
                    //zorg dat er geen display is voor hidden fields
                    $element->removeDecorator('htmlTag');
                    $element->removeDecorator('Label');
                case 'Zend_Form_Element_Submit':
                    //Just leave this one out of the tabs
                    break;

                default:
                    if (isset($tab)) {
                        if ($element instanceof Zend_Form_DisplayGroup) {
                            $groupElements = $element->getElements();
                            $groupName = $element->getName();
                            $options = $element->getAttribs();
                            $options['description'] = $element->getDescription();

                            $elements = array();
                            foreach ($groupElements as $groupElement) {
                                $newElement = clone $groupElement;
                                $tab->addElement($newElement);
                                $elements[] = $newElement->getName();
                            }
                            $removeGrp[] = $groupName;
                            foreach ($elements as $oldElement) {
                                $remove[] = $oldElement;
                            }
                            $tab->addDisplayGroup($elements, $groupName, $options);
                        } else {
                            $tab->addElement($element);
                            $remove[] = $element->getName();
                        }
                    } else {
                        unset($tab);
                    }
                    break;
            }
        }
        //Get the final tab info
        if (isset($tab)) {
            $tabs[$tab->getName()] = $tab;
        }

        //Cleanup the form, do this now, because otherwise the loop was reset when removing items
        if (isset($remove)) {
            foreach($remove as $name) {
                $form->removeElement($name);
            }
        }
        if (isset($removeGrp)) {
            foreach($removeGrp as $name) {
            $form->removeDisplayGroup($name);
            }
        }

        //Now add the tabs as displaygroups
        if (isset($tabs) && is_array($tabs)) {
            $form->addSubForms($tabs);
        } else {
            //Ok no tabs defined... maybe we should do something for display here...
        }

        /**
         * If the form is populated... and we have a tab set... select it
         */
        $form->selectTab($form->getValue('tab'));
    }

    /**
     * Perfoms some actions needed to initialize the form
     */
    public function init()
    {
        /**
         * Make it a JQuery form
         *
         * NOTE: Do this for all subforms you add afterwards
         */
        ZendX_JQuery::enableForm($this);

        $this->addPrefixPath('Gems_JQuery_Form_Decorator', 'Gems/JQuery/Form/Decorator', 'decorator')
             ->addElementPrefixPath('Gems_JQuery_Form_Decorator', 'Gems/JQuery/Form/Decorator', 'decorator')
             ->addDisplayGroupPrefixPath('Gems_JQuery_Form_Decorator', 'Gems/JQuery/Form/Decorator', 'decorator');

        /**
         * You must set the form id so that you can add your tabPanes to the tabContainer
         */
        if (is_null($this->getAttrib('id'))) $this->setAttrib('id', 'mainForm');

        /**
         * Now we add a hidden element to hold the selected tab
         */
        $this->addElement(new Zend_Form_Element_Hidden('tab'));

        $jquery = $this->getView()->jQuery();
        /**
         * This script handles saving the tab to our hidden input when a new tab is showed
         */
        $js = sprintf('%1$s("#tabContainer").bind( "tabsshow", function(event, ui) {
            var $tabs = %1$s("#tabContainer").tabs();
            var selected = $tabs.tabs("option", "selected"); // => 0
            %1$s("#%2$s input#tab").val(selected);
            });',
            ZendX_JQuery_View_Helper_JQuery::getJQueryHandler(),
            $this->getAttrib('id')
        );
        $jquery->addOnLoad($js);
    }

    public function loadDefaultDecorators() {
        if ($this->loadDefaultDecoratorsIsDisabled()) {
            return;
        }

        $decorators = $this->getDecorators();
        if (empty($decorators)) {
            $this->setDecorators(array(
            array('TabErrors'),
            array('decorator' => array('SubformElements' => 'FormElements')),
            array('HtmlTag', array('tag' => 'div', 'id' => 'tabContainer', 'class' => 'mainForm')),
            array('TabContainer', array('id' => 'tabContainer', 'style' => 'width: 99%;')),
            'FormElements',
            'Form'
            ));
        }
    }

    public function selectTab($tabIdx) {
        $this->getElement('tab')->setValue($tabIdx);
        $this->setAttrib('selected', $tabIdx);
    }

    /**
     * Set the view object
     *
     * @param Zend_View_Interface $view
     * @return Gems_TabForm
     */
    public function setView(Zend_View_Interface $view = null) {
        $form = parent::setView($view);
        ZendX_JQuery::enableView($view);

        if (false === $view->getPluginLoader('helper')->getPaths('Gems_JQuery_View_Helper')) {
            $view->addHelperPath('Gems/JQuery/View/Helper', 'Gems_JQuery_View_Helper');
        }

        return $form;
    }

    public function addDisplayGroup(array $elements, $name, $options = null) {
        //Add the group as usual
        parent::addDisplayGroup($elements, $name, $options);

        //Retrieve it and set decorators
        $group = $this->getDisplayGroup($name);
        $group->setDecorators( array('FormElements',
                            array('HtmlTag', array('tag' => 'div', 'class' => $group->getName(). ' ' . $group->getAttrib('class')))
                            ));
    }
}