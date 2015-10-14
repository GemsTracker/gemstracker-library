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
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Adds Gems specific display details and helper functions:
 *
 * Items set are:
 * = Default route: 'show'
 * - Display class: 'formTable'
 * - \Gems_Form use: createForm()
 * - Table display: beforeDispay()
 *
 * Extra helpers are:
 * - Form title:   getTitle()
 * - Menu helpers: $this->menu, beforeDispay() & getMenuList()
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
abstract class Gems_Snippets_ModelFormSnippetAbstract extends \MUtil_Snippets_ModelFormSnippetAbstract
{
    /**
     *
     * @var \Gems_AccessLog
     */
    protected $accesslog;

    /**
     *
     * @var \Zend_Cache_Core
     */
    protected $cache;

    /**
     * Variable to set tags for cache cleanup after changes
     *
     * @var array
     */
    protected $cacheTags;

    /**
     * Shortfix to add class attribute
     *
     * @var string
     */
    protected $class = 'formTable';

    /**
     * An optional title for the form. replacing the current generic form title.
     *
     * @var string Optional
     */
    protected $formTitle;

    /**
     * When set getTopic uses this function instead of parent class.
     *
     * @var callable
     */
    protected $topicCallable;

    /**
     * Required
     *
     * @var \Gems_Menu
     */
    protected $menu;

    /**
     *
     * @var boolean
     */
    protected $menuShowChildren = false;

    /**
     *
     * @var boolean
     */
    protected $menuShowSiblings = false;

    /**
     *
     * @var \Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     * The name of the action to forward to after form completion
     *
     * @var string
     */
    protected $routeAction = 'show';

    /**
     * When true a tabbed form is used.
     *
     * @var boolean
     */
    protected $useTabbedForm = false;

    /**
     * Adds elements from the model to the bridge that creates the form.
     *
     * Overrule this function to add different elements to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_FormBridgeInterface $bridge
     * @param \MUtil_Model_ModelAbstract $model
     */
    protected function addFormElements(\MUtil_Model_Bridge_FormBridgeInterface $bridge, \MUtil_Model_ModelAbstract $model)
    {
        if (! $bridge->getForm() instanceof \Gems_TabForm) {
            parent::addFormElements($bridge, $model);
            return;
        }

        //Get all elements in the model if not already done
        $this->initItems();

        // Add 'tooltip' to the allowed displayoptions
        $displayOptions = $bridge->getAllowedOptions(\MUtil_Model_Bridge_FormBridge::DISPLAY_OPTIONS);
        if (!array_search('tooltip', $displayOptions)) {
            $displayOptions[] = 'tooltip';
            $bridge->setAllowedOptions(\MUtil_Model_Bridge_FormBridge::DISPLAY_OPTIONS, $displayOptions);
        }

        $tab    = 0;
        $group  = 0;
        $oldTab = null;
        // \MUtil_Echo::track($model->getItemsOrdered());
        foreach ($model->getItemsOrdered() as $name) {
            // Get all options at once
            $modelOptions = $model->get($name);
            $tabName      = $model->get($name, 'tab');
            if ($tabName && ($tabName !== $oldTab)) {
                if (isset($modelOptions['elementClass']) && ('tab' == strtolower($modelOptions['elementClass']))) {
                    $bridge->addTab('tab' . $tab, $modelOptions + array('value' => $tabName));
                } else {
                    $bridge->addTab('tab' . $tab, 'value', $tabName);
                }
                $oldTab = $tabName;
                $tab++;
            }

            if ($model->has($name, 'label')) {
                $bridge->add($name);

                if ($theName = $model->get($name, 'startGroup')) {
                    //We start a new group here!
                    $groupElements   = array();
                    $groupElements[] = $name;
                    $groupName       = $theName;
                } elseif ($theName = $model->get($name, 'endGroup')) {
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
            unset($this->_items[$name]);
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
        if ($this->_form instanceof \Gems_TabForm) {
            $this->_form->resetContext();
        }
        parent::addSaveButton();
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        if ($this->project instanceof \Gems_Project_ProjectSettings) {
            $this->useCsrf = $this->project->useCsrfCheck();
        }
    }

    /**
     * Hook that allows actions when data was saved
     *
     * When not rerouted, the form will be populated afterwards
     *
     * @param int $changed The number of changed rows (0 or 1 usually, but can be more)
     */
    protected function afterSave($changed)
    {
        parent::afterSave($changed);

        if ($changed) {
            $this->accesslog->logChange($this->request, null, $this->formData);
        }

        if ($this->cacheTags && ($this->cache instanceof \Zend_Cache_Core)) {
            $this->cache->clean(\Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, (array) $this->cacheTags);
        }
    }

    /**
     * Perform some actions on the form, right before it is displayed but already populated
     *
     * Here we add the table display to the form.
     *
     * @return \Zend_Form
     */
    public function beforeDisplay()
    {
        if ($this->_form instanceof \Gems_TabForm) {
            if ($links = $this->getMenuList()) {
                $linkContainer = \MUtil_Html::create()->div(array('class' => 'element-container-labelless'));
                $linkContainer[] = $links;

                $element = $this->_form->createElement('html', 'formLinks');
                $element->setValue($linkContainer)
                        ->setOrder(999)
                        ->removeDecorator('HtmlTag')
                        ->removeDecorator('Label')
                        ->removeDecorator('DtDdWrapper');

                $this->_form->resetContext();
                $this->_form->addElement($element);

                if (is_null($this->_form->getDisplayGroup(\Gems_TabForm::GROUP_OTHER))) {
                    $this->_form->addDisplayGroup(array($element), \Gems_TabForm::GROUP_OTHER);
                } else {
                    $this->_form->getDisplayGroup(\Gems_TabForm::GROUP_OTHER)->addElement($element);
                }
            }
        } else {
            if (\MUtil_Bootstrap::enabled() !== true) {
                $table = new \MUtil_Html_TableElement(array('class' => $this->class));
                $table->setAsFormLayout($this->_form, true, true);

                // There is only one row with formLayout, so all in output fields get class.
                $table['tbody'][0][0]->appendAttrib('class', $this->labelClass);

                if ($links = $this->getMenuList()) {
                    $table->tf(); // Add empty cell, no label
                    $table->tf($links);
                }
            } elseif($links = $this->getMenuList()) {
                $element = $this->_form->createElement('html', 'menuLinks');
                $element->setValue($links);
                $element->setOrder(999);
                $this->_form->addElement($element);
            }
        }
    }

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        return $this->menu && parent::checkRegistryRequestsAnswers();
    }

    /**
     * Creates an empty form. Allows overruling in sub-classes.
     *
     * @param mixed $options
     * @return \Zend_Form
     */
    protected function createForm($options = null)
    {
        if ($this->useTabbedForm) {
            return new \Gems_TabForm($options);
        }
        if (\MUtil_Bootstrap::enabled()) {
            if (!isset($options['class'])) {
                $options['class'] = 'form-horizontal';
            }

            if (!isset($options['role'])) {
                $options['role'] = 'form';
            }
        }
        return new \Gems_Form($options);
    }

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param \Zend_View_Abstract $view Just in case it is needed here
     * @return \MUtil_Html_HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view)
    {
        $htmlDiv = \MUtil_Html::div();

        $htmlDiv->h3($this->getTitle(), array('class' => 'title'));

        $form = parent::getHtmlOutput($view);

        $htmlDiv[] = $form;

        return $htmlDiv;
    }

    /**
     * overrule to add your own buttons.
     *
     * @return \Gems_Menu_MenuList
     */
    protected function getMenuList()
    {
        $links = $this->menu->getMenuList();

        $links->addParameterSources($this->request, $this->menu->getParameterSource());
        $links->addCurrentParent($this->_('Cancel'));

        if ($this->menuShowSiblings) {
            $links->addCurrentSiblings();
        }

        if ($this->menuShowChildren) {
            $links->addCurrentChildren();
        }

        return $links;
    }

    /**
     * Retrieve the header title to display
     *
     * @return string
     */
    protected function getTitle()
    {
        if ($this->formTitle) {
            return $this->formTitle;
        } elseif ($this->createData) {
            return sprintf($this->_('New %s...'), $this->getTopic());
        } else {
            return sprintf($this->_('Edit %s'), $this->getTopic());
        }
    }

    /**
     * Helper function to allow generalized statements about the items in the model to used specific item names.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        if (is_callable($this->topicCallable)) {
            return call_user_func($this->topicCallable, $count);
        } else {
            return parent::getTopic($count);
        }
    }

    /**
     * If menu item does not exist or is not allowed, redirect to index
     *
     * @return \Gems_Snippets_ModelFormSnippetAbstract
     */
    protected function setAfterSaveRoute()
    {
        parent::setAfterSaveRoute();

        if (is_array($this->afterSaveRouteUrl)) {
            // Make sure controller is set
            if (!array_key_exists('controller', $this->afterSaveRouteUrl)) {
                $this->afterSaveRouteUrl['controller'] = $this->request->getControllerName();
            }

            // Search array for menu item
            $find['controller'] = $this->afterSaveRouteUrl['controller'];
            $find['action'] = $this->afterSaveRouteUrl['action'];

            // If not allowed, redirect to index
            if (null == $this->menu->find($find)) {
                $this->afterSaveRouteUrl['action'] = 'index';
                $this->resetRoute = true;
            }
        }
        // \MUtil_Echo::track($this->routeAction, $this->resetRoute);

        return $this;
    }
}
