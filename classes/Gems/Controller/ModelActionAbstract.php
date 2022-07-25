<?php

/**
 *
 * @package    Gems
 * @subpackage Controller
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Controller;

/**
 * Extends the standard \MUtil\Controller\ModelActionAbstract with parameters
 * and functions for working with loader and menu items.
 *
 * @package    Gems
 * @subpackage Controller
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
abstract class ModelActionAbstract extends \MUtil\Controller\ModelActionAbstract
{
    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;

    /**
     *
     * @var \Gems\Loader
     */
    public $loader;

    /**
     *
     * @var \Gems\Menu
     */
    public $menu;

    /**
     *
     * @var \Gems\Util
     */
    public $util;

    /**
     *
     * @param mixed $options
     * @return \Gems\Form
     */
    protected function createForm($options = array()): \Zend_Form
    {
        if (!isset($options['class'])) {
            $options['class'] = 'form-horizontal';
        }

        if (!isset($options['role'])) {
            $options['role'] = 'form';
        }
        $form = new \Gems\Form($options);

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
                if (($parentItem instanceof \Gems\Menu\SubMenuItem) && $parentItem->has('controller')) {
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
        $actions = \MUtil\Ra::args(func_get_args());
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
     * \MUtil\Model::REQUEST_ID.
     *
     * @return mixed
     */
    public function getInstanceId()
    {
        if ($id = $this->_getParam(\MUtil\Model::REQUEST_ID)) {
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
            \Gems\Html::init();
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
