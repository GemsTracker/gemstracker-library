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
 * @subpackage Controller
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Extends the standard \MUtil_Controller_ModelActionAbstract with parameters
 * and functions for working with loader and menu items.
 *
 * @package    Gems
 * @subpackage Controller
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
abstract class Gems_Controller_ModelActionAbstract extends \MUtil_Controller_ModelActionAbstract
{
    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;

    /**
     *
     * @var \Gems_Loader
     */
    public $loader;

    /**
     *
     * @var \Gems_Menu
     */
    public $menu;

    /**
     *
     * @var \Gems_Util
     */
    public $util;

    /**
     *
     * @param mixed $options
     * @return \Gems_Form
     */
    protected function createForm($options = array())
    {
        if (\MUtil_Bootstrap::enabled()) {
            if (!isset($options['class'])) {
                $options['class'] = 'form-horizontal';
            }

            if (!isset($options['role'])) {
                $options['role'] = 'form';
            }
        }
        $form = new \Gems_Form($options);

        return $form;
    }

    protected function createMenuLinks($includeLevel = 2, $parentLabel = true)
    {
        if ($currentItem  = $this->menu->getCurrent()) {
            $links        = array();
            $childItems   = $currentItem->getChildren();
            $parameters   = $currentItem->getParameters();
            $request      = $this->getRequest();
            $showDisabled = $includeLevel > 99;
            $menuSource   = $this->menu->getParameterSource();

            if ($parentItem = $currentItem->getParent()) {
                // Add only if not toplevel.
                if (($parentItem instanceof \Gems_Menu_SubMenuItem) && $parentItem->has('controller')) {
                    $key = $parentItem->get('controller') . '.' . $parentItem->get('action');
                    if ($parentLabel) {
                        if (true === $parentLabel) {
                            $parentLabel = $this->_('Cancel');
                        }
                        $links[$key] = $parentItem->toActionLink($request, $this, $menuSource, $parentLabel);
                    } else {
                        $links[$key] = $parentItem->toActionLink($request, $this, $menuSource);
                    }
                    if ($includeLevel > 1) {
                        $childItems = array_merge($parentItem->getChildren(), $childItems);
                    }
                }
            }

            if ($includeLevel < 1) {
                return $links;
            }

            //The reset parameter blocks the display of buttons, so we unset it
            unset($parameters['reset']);
            if ($childItems) {
                foreach ($childItems as $menuItem) {
                    if ($menuItem !== $currentItem) {
                        // Select only children with the same parameters
                        if ($menuItem->getParameters() == $parameters) {
                            // And buttons only if include level higher than 2.
                            if (($includeLevel > 2) || (! $menuItem->get('button_only'))) {
                                if ($link = $menuItem->toActionLink($request, $this, $menuSource, $showDisabled)) {
                                    $key = $menuItem->get('controller') . '.' . $menuItem->get('action');
                                    $links[$key] = $link;
                                }
                            }
                        }
                    }
                }
            }

            return $links;
        }
    }

    protected function findAllowedMenuItem($action)
    {
        $actions = \MUtil_Ra::args(func_get_args());
        $controller = $this->_getParam('controller');

        foreach ($actions as $action) {
            $menuItem = $this->menu->find(array('controller' => $controller, 'action' => $action, 'allowed' => true));

            if ($menuItem) {
                return $menuItem;
            }
        }
    }

    /**
     * Return the current request ID, if any.
     *
     * Overrule this function if the last item in the page title
     * should be something other than te value of
     * \MUtil_Model::REQUEST_ID.
     *
     * @return mixed
     */
    public function getInstanceId()
    {
        if ($id = $this->_getParam(\MUtil_Model::REQUEST_ID)) {
            return $id;
        }
    }

    /**
     * Returns the current html/head/title for this page.
     *
     * If the title is an array the seperator concatenates the parts.
     *
     * @param string $separator
     * @return string
     */
    public function getTitle($separator = null)
    {
        if ($title_set = parent::getTitle($separator)) {
            return $title_set;
        }

        $title = array();
        foreach($this->menu->getActivePath($this->getRequest()) as $menuItem) {
            $title[] = $menuItem->get('label');
        }
        if ($id = $this->getInstanceId()) {
            $title[] = $id;
        }

        return implode($separator, $title);
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    abstract public function getTopic($count = 1);


    /**
     * Helper function to allow generalized treatment of the header.
     *
     * return $string
     */
    abstract public function getTopicTitle();

    /**
     * Intializes the html component.
     *
     * @param boolean $reset Throws away any existing html output when true
     * @return void
     */
    public function initHtml($reset = false)
    {
        if (! $this->html) {
            \Gems_Html::init();
        }

        parent::initHtml($reset);
    }

    /**
     * Stub for overruling default snippet loader initiation.
     */
    protected function loadSnippetLoader()
    {
        // Create the snippet with this controller as the parameter source
        $this->snippetLoader = $this->loader->getSnippetLoader($this);
    }
}
