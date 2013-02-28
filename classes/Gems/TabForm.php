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
 * Creates a form using tab-layout where each tab is a subform
 *
 * @package    Gems
 * @subpackage Form
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */
class Gems_TabForm extends Gems_Form
{
    /**
     * Holds the last tab we added information to
     *
     * @var Gems_Form_TabSubForm
     */
    private $currentTab = null;

    public function __construct($options = null)
    {
        parent::__construct($options);

        /**
         * Make it a JQuery form
         *
         * NOTE: Do this for all subforms you add afterwards
         */
        $this->activateJQuery();

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

    /**
     * Add an element to the form, when a tab (subform) had been added, it will return
     * the subform instead of the form, keep this in mind when chaining methods
     *
     * @param  string|Zend_Form_Element $element
     * @param  string $name
     * @param  array|Zend_Config $options
     * @throws Zend_Form_Exception on invalid element
     * @return Gems_TabForm|Gems_Form_TabSubForm
     */
    public function addElement($element, $name = null, $options = null)
    {
        if ($this->currentTab) {
            return $this->currentTab->addElement($element, $name, $options);
        } else {
            parent::addElement($element, $name, $options);
            if (is_string($element)) {
                $element = $this->getElement($element);
            }
            if ($element instanceof Zend_Form_Element_Hidden) {
                //Remove decorators
                $element->removeDecorator('htmlTag');
                $element->removeDecorator('Label');
            } elseif ($element instanceof Zend_Form_Element) {
                $error = $element->getDecorator('Errors');
                if ($error instanceof Zend_Form_Decorator_Errors) {
                    $element->removeDecorator('Errors');
                    $element->addDecorator($error);
                }
            }
            return $this;
        }
    }

    /**
     * Add a tab to the form
     *
     * @param string $name
     * @param string $title
     * @return Gems_Form_TabSubForm
     */
    public function addTab($name, $title)
    {
        if ($title instanceof MUtil_Html_HtmlInterface) {
            $title = $title->render($this->getView());
        }
        $tab = new Gems_Form_TabSubForm(array('name' => $name, 'title' => strip_tags($title)));
        $this->currentTab = $tab;
        $this->addSubForm($tab, $name);
        return $tab;
    }

    /**
     * Add an element to the form, when a tab (subform) had been added, it will return
     * the subform instead of the form, keep this in mind when chaining methods
     *
     * @param  array $elements
     * @param  string $name
     * @param  array|Zend_Config $options
     * @return Gems_TabForm|Gems_Form_TabSubForm
     * @throws Zend_Form_Exception if no valid elements provided
     */
    public function addDisplayGroup(array $elements, $name, $options = null) {
        if ($this->currentTab) {
            return $this->currentTab->addDisplayGroup($elements, $name, $options);
        } else {
            //Add the group as usual
            parent::addDisplayGroup($elements, $name, $options);

            //Retrieve it and set decorators
            $group = $this->getDisplayGroup($name);
            $group->setDecorators( array('FormElements',
                                array('HtmlTag', array('tag' => 'div', 'class' => $group->getName(). ' ' . $group->getAttrib('class')))
                                ));
            return $this;
        }
    }

    /**
     * Return a display group, use recursive search in subforms to provide a transparent experience
     * with tabs
     *
     * @param  string $name
     * @return Zend_Form_DisplayGroup|null
     */
    public function getDisplayGroup($name)
    {
        if ($group = parent::getDisplayGroup($name)) {
            return $group;
        } else {
            $subforms = $this->getSubForms();
            foreach($subforms as $subform) {
                if ($group = $subform->getDisplayGroup($name)) {
                    return $group;
                }
            }
            return;
        }
    }

    /**
     * Retrieve a single element, use recursive search in subforms to provide a transparent experience
     * with tabs
     *
     * @param  string $name
     * @return Zend_Form_Element|null
     */
    public function getElement($name)
    {
        if ($element = parent::getElement($name)) {
            return $element;
        } else {
            $subforms = $this->getSubForms();
            foreach($subforms as $subform) {
                if ($element = $subform->getElement($name)) {
                    return $element;
                }
            }
            return;
        }
    }

    /**
     * Retrieve a named tab (subform) and set the active tab to this one
     *
     * @param string $name
     * @return Gems_Form_TabSubForm
     */
    public function getTab($name)
    {
        $tab = $this->getSubForm($name);
        $this->currentTab = $tab;
        return $tab;
    }

    /**
     * Load the default decorators
     *
     * @return void
     */
    public function loadDefaultDecorators() {
        if ($this->loadDefaultDecoratorsIsDisabled()) {
            return;
        }

        $decorators = $this->getDecorators();
        if (empty($decorators)) {
            $this->setDecorators(array(
            'TabErrors',
            array(array('SubformElements' => 'FormElements')),
            array('HtmlTag', array('tag' => 'div', 'id' => 'tabContainer', 'class' => 'mainForm')),
            array('TabContainer', array('id' => 'tabContainer', 'style' => 'width: 99%;')),
            'FormElements',
            'Form'
            ));
        }
    }

    /**
     * Reset the currentTab to be the main form again
     *
     * As addElement and addDisplayGroup provide a fluent way of working with subforms
     * we need to provide a method to skip back to the main form again.
     */
    public function resetContext() {
        $this->currentTab = null;
    }

    /**
     * Select a tab by it's numerical index
     *
     * @param int $tabIdx
     */
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
        /**
         * If the form is populated... and we have a tab set... select it
         */
        if ($tab = $this->getValue('tab')) {
            $this->selectTab($tab);
        }

        parent::setView($view);

        if ($this->_view !== $view) {
            $this->activateJQuery();
            if (false === $view->getPluginLoader('helper')->getPaths('Gems_JQuery_View_Helper')) {
                $view->addHelperPath('Gems/JQuery/View/Helper', 'Gems_JQuery_View_Helper');
            }
        }

        return $this;
    }

    /**
     * Retrieve all form element values
     *
     * Fix for ZF error where subform values will be pushed into an array with key: formname
     * for compatibility both are now in the result array
     *
     * @param  bool $suppressArrayNotation
     * @return array
     */
    public function getValues($suppressArrayNotation = false)
    {
        $values = parent::getValues($suppressArrayNotation);

        foreach ($this->getSubForms() as $key => $subForm) {
            $values = $this->_array_replace_recursive($values, $values[$key]);
        }

        return $values;
    }
}