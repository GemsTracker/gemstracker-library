<?php

/**
 * Copyright (c) 2015, Erasmus MC
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
 * DISCLAIMED. IN NO EVENT SHALL MAGNAFACTA BE LIABLE FOR ANY
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
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: FormSnippetAbstract.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

namespace Gems\Snippets;

use MUtil\Snippets\FormSnippetAbstract as MUtilFormSnippetAbstract;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 14-okt-2015 11:26:15
 */
abstract class FormSnippetAbstract extends MUtilFormSnippetAbstract
{
    /**
     *
     * @var \Gems_AccessLog
     */
    protected $accesslog;

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
     * Add the elements to the form
     *
     * @param \Zend_Form $form
     */
    // abstract protected function addFormElements(\Zend_Form $form);

    /**
     * Creates an empty form. Allows overruling in sub-classes.
     *
     * @param mixed $options
     * @return \Zend_Form
     */
    protected function createForm($options = null)
    {
        $form = new \Gems_Form($options);

        return $form;
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
        if ($this->_csrf) {
            $this->_csrf->initCsrfToken();
        }

        $links = $this->getMenuList();
        if (\MUtil_Bootstrap::enabled()) {
            if ($links) {
                $element = $this->_form->createElement('html', 'menuLinks');
                $element->setValue($links);
                $element->setOrder(999);
                $this->_form->addElement($element);
            }
        } else {
            $table = new \MUtil_Html_TableElement(array('class' => $this->class));
            $table->setAsFormLayout($this->_form, true, true);

            // There is only one row with formLayout, so all in output fields get class.
            $table['tbody'][0][0]->appendAttrib('class', $this->labelClass);

            if ($links) {
                $table->tf(); // Add empty cell, no label
                $table->tf($links);
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
}
