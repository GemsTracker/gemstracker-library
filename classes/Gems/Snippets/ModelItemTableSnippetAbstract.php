<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

/**
 * Displays each fields of a single item in a model in a row in a Html table
 * the model set through the $model snippet parameter.
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.2
 */
abstract class Gems_Snippets_ModelItemTableSnippetAbstract extends \MUtil_Snippets_ModelVerticalTableSnippetAbstract
{
    /**
     * Edit the item when it is clicked (provided the user has the right)
     *
     * @var boolean
     */
    protected $addOnclickEdit = true;

    /**
     * Shortfix to add class attribute
     *
     * @var string
     */
    protected $class = 'displayer table';

    /**
     * When true the menu is displayed
     *
     * @var boolean
     */
    protected $displayMenu = true;

    /**
     * Optional title to display at the head of this page.
     *
     * @var string Optional
     */
    protected $displayTitle;

    /**
     * Required
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     * Required
     *
     * @var \Gems_Menu
     */
    protected $menu;

    /**
     * An optional list menu items
     *
     * @var \Gems_Menu_MenuList
     */
    protected $menuList = null;

    /**
     * Required
     *
     * @var \Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     * Adds rows from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_VerticalTableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function addShowTableRows(\MUtil_Model_Bridge_VerticalTableBridge $bridge, \MUtil_Model_ModelAbstract $model)
    {
        if ($this->addOnclickEdit) {
            /*$menuItem = $this->getEditMenuItem();
            if ($menuItem) {
                // Add click to edit
                $bridge->tbody()->onclick = array('location.href=\'', $menuItem->toHRefAttribute($this->request), '\';');
            }*/
        }

        parent::addShowTableRows($bridge, $model);
    }

    /**
     * Finds a specific active menu item
     *
     * @param string $controller
     * @param string $action
     * @return \Gems_Menu_SubMenuItem
     */
    protected function findMenuItem($controller, $action = 'index')
    {
        return $this->menu->find(array('controller' => $controller, 'action' => $action, 'allowed' => true));
    }

    /**
     * Returns an edit menu item, if access is allowed by privileges
     *
     * @return \Gems_Menu_SubMenuItem
     */
    protected function getEditMenuItem()
    {
        return $this->findMenuItem($this->request->getControllerName(), 'edit');
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
        if ($table = parent::getHtmlOutput($view)) {
            if ($title = $this->getTitle()) {
                $htmlDiv = \MUtil_Html::div(array('renderWithoutContent' => false));

                $htmlDiv->h3($title);

                $this->applyHtmlAttributes($table);

                $htmlDiv[] = $table;

                return $htmlDiv;
            } else {
                return $table;
            }
        }
    }

    /**
     * An optional title for the head of the page.
     *
     * @return string
     */
    protected function getTitle()
    {
        return $this->displayTitle;
    }

    /**
     * Set the footer of the browse table.
     *
     * Overrule this function to set the header differently, without
     * having to recode the core table building code.
     *
     * @param \MUtil_Model_Bridge_VerticalTableBridge $bridge
     * @param \MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function setShowTableFooter(\MUtil_Model_Bridge_VerticalTableBridge $bridge, \MUtil_Model_ModelAbstract $model)
    {
        /*if ($this->displayMenu) {
            if (! $this->menuList) {
                $this->menuList = $this->menu->getCurrentMenuList($this->request, $this->_('Cancel'));
            }
            if ($this->menuList instanceof \Gems_Menu_MenuList) {
                $this->menuList->addParameterSources($bridge);
            }

            $bridge->tfrow($this->menuList, array('class' => 'centerAlign'));
        }*/
    }
}
