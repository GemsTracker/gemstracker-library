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
 * - Display class: 'browser'
 *
 * Extra helpers are:
 * - Keyboard access: $this->keyboard & getHtmlOutput()
 * - Menu helpers:    $this->menu, findMenuItem()
 * - Sort parameters: $sortParamAsc & $sortParamDesc
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.2
 */
abstract class Gems_Snippets_ModelTableSnippetAbstract extends \MUtil_Snippets_ModelTableSnippetAbstract
{
    /**
     * Shortfix to add class attribute
     *
     * @var string
     */
    protected $class = 'browser table';

    /**
     *
     * @var string The id of a div that contains the table.
     */
    protected $containingId;

    /**
     * The default search data to use.
     *
     * @var array()
     */
    protected $defaultSearchData = array();

    /**
     * Use keyboard to select row
     *
     * @var boolean
     */
    public $keyboard = false;

    /**
     * Make sure the keyboard id is used only once
     *
     * @var boolean
     */
    public static $keyboardUsed = false;

    /**
     *
     * @var \Gems_Menu
     */
    public $menu;

    /**
     * The default controller for menu actions, if null the current controller is used.
     *
     * @var array (int/controller => action)
     */
    public $menuActionController = null;

    /**
     * Menu actions to show in Edit box.
     *
     * If controller is numeric $menuActionController is used, otherwise
     * the key specifies the controller.
     *
     * @var array (int/controller => action)
     */
    public $menuEditActions = array('edit');

    /**
     * Menu actions to show in Show box.
     *
     * If controller is numeric $menuActionController is used, otherwise
     * the key specifies the controller.
     *
     * @var array (int/controller => action)
     */
    public $menuShowActions = array('show');

    /**
     * Option to manually diasable the menu
     *
     * @var boolean
     */
    protected $showMenu = true;

    /**
     * The $request param that stores the ascending sort
     *
     * @var string
     */
    protected $sortParamAsc = 'asrt';

    /**
     * The $request param that stores the descending sort
     *
     * @var string
     */
    protected $sortParamDesc = 'dsrt';

    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_TableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function addBrowseTableColumns(\MUtil_Model_Bridge_TableBridge $bridge, \MUtil_Model_ModelAbstract $model)
    {
        if ($model->has('row_class')) {
            $bridge->getTable()->tbody()->getFirst(true)->appendAttrib('class', $bridge->row_class);
        }

        if ($this->showMenu) {
            $showMenuItems = $this->getShowMenuItems();

            foreach ($showMenuItems as $menuItem) {
                $bridge->addItemLink($menuItem->toActionLinkLower($this->request, $bridge));
            }
        }

        // make sure search results are highlighted
        $this->applyTextMarker();

        parent::addBrowseTableColumns($bridge, $model);

        if ($this->showMenu) {
            $editMenuItems = $this->getEditMenuItems();

            foreach ($editMenuItems as $menuItem) {
                $bridge->addItemLink($menuItem->toActionLinkLower($this->request, $bridge));
            }
        }
    }

    /**
     * Add the paginator panel to the table.
     *
     * Only called when $this->browse is true. Overrule this function
     * to define your own method.
     *
     * $param \Zend_Paginator $paginator
     */
    protected function addPaginator(\MUtil_Html_TableElement $table, \Zend_Paginator $paginator)
    {
        $table->tfrow()->pagePanel($paginator, $this->request, $this->translate);
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

        if (! $this->menuActionController) {
            $this->menuActionController = $this->request->getControllerName();
        }
    }

    /**
     * Make sure generic search text results are marked
     *
     * @return void
     */
    protected function applyTextMarker()
    {
        $model = $this->getModel();

        $textKey = $model->getTextFilter();
        $filter  = $model->getFilter();

        if (isset($filter[$textKey])) {
            $searchText = $filter[$textKey];
            // \MUtil_Echo::r('[' . $searchText . ']');
            $marker = new \MUtil_Html_Marker($model->getTextSearches($searchText), 'strong', 'UTF-8');
            foreach ($model->getItemNames() as $name) {
                if ($model->get($name, 'label')) {
                    $model->set($name, 'markCallback', array($marker, 'mark'));
                }
            }
        }
    }

    /**
     *
     * @param mixed $parameterSource
     * @param string $controller
     * @param string $action
     * @param string $label
     * @return \MUtil_Html_AElement
     */
    public function createMenuLink($parameterSource, $controller, $action = 'index', $label = null)
    {
        $menuItem = $this->findMenuItem($controller, $action);
        if ($menuItem) {
            return $menuItem->toActionLinkLower($this->request, $parameterSource, $label);
        }
    }

    /**
     * Finds a specific active menu item
     *
     * @param string $defaultController
     * @param string|array $actions
     * @return \Gems_Menu_SubMenuItem The first that
     * @deprecated since 1.7.1, use findMenuItems()
     */
    protected function findMenuItem($defaultController, $actions = 'index')
    {
        foreach ((array) $actions as $key => $action) {
            $controller = is_int($key) ? $defaultController : $key;
            $item       = $this->menu->find(array('controller' => $controller, 'action' => $action, 'allowed' => true));

            if ($item) {
                return $item;
            }
        }
    }

    /**
     * Finds a specific active menu item
     *
     * @param string $defaultController
     * @param string|array $actions
     * @return array of \Gems_Menu_SubMenuItem
     */
    protected function findMenuItems($defaultController, $actions = array('index'))
    {
        $output = array();

        foreach ((array) $actions as $key => $action) {
            $controller = is_int($key) ? $defaultController : $key;
            $item       = $this->menu->find(array('controller' => $controller, 'action' => $action, 'allowed' => true));

            if ($item) {
                $output[] = $item;
            }
        }

        return $output;
    }

    /**
     * Returns an edit menu item, if access is allowed by privileges
     *
     * @deprecated since 1.7.1, use getEditMenuItems()
     * @return \Gems_Menu_SubMenuItem
     */
    protected function getEditMenuItem()
    {
        if ($this->menuEditActions) {
            return $this->findMenuItem($this->menuActionController, $this->menuEditActions);
        }
    }

    /**
     * Returns an edit menu item, if access is allowed by privileges
     *
     * @return \Gems_Menu_SubMenuItem
     */
    protected function getEditMenuItems()
    {
        if ($this->menuEditActions) {
            return $this->findMenuItems($this->menuActionController, $this->menuEditActions);
        }
        return array();
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
        $table = parent::getHtmlOutput($view);
        $table->getOnEmpty()->class = 'centerAlign';

        if (($this->containingId || $this->keyboard) && (! self::$keyboardUsed)) {
            // Assign keyboard tracking only once
            self::$keyboardUsed = true;

            $this->applyHtmlAttributes($table);

            // If we are already in a containing div it is simple
            if ($this->containingId) {
                return array($table, new \Gems_JQuery_TableRowKeySelector($this->containingId));
            }

            // Create a new containing div
            $div = \MUtil_Html::create()->div(array('id' => 'keys_target', 'class' => 'table-container'), $table);

            return array($div, new \Gems_JQuery_TableRowKeySelector($div));

        } else {
            return $table;
        }
    }

    /**
     * Returns a show menu item, if access is allowed by privileges
     *
     * @deprecated since 1.7.1, use getShowMenuItems()
     * @return \Gems_Menu_SubMenuItem
     */
    protected function getShowMenuItem()
    {
        if ($this->menuShowActions) {
            return $this->findMenuItem($this->menuActionController, $this->menuShowActions);
        }
    }

    /**
     * Returns a show menu item, if access is allowed by privileges
     *
     * @return \Gems_Menu_SubMenuItem
     */
    protected function getShowMenuItems()
    {
        if ($this->menuShowActions) {
            return $this->findMenuItems($this->menuActionController, $this->menuShowActions);
        }
        return array();
    }
}
