<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets;

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
abstract class ModelItemTableSnippetAbstract extends \MUtil\Snippets\ModelVerticalTableSnippetAbstract
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
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     * Required
     *
     * @var \Gems\Menu
     */
    protected $menu;

    /**
     * An optional list menu items
     *
     * @var \Gems\Menu\MenuList
     */
    protected $menuList = null;

    /**
     * Adds rows from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil\Model\Bridge\VerticalTableBridge $bridge
     * @param \MUtil\Model\ModelAbstract $model
     * @return void
     */
    protected function addShowTableRows(\MUtil\Model\Bridge\VerticalTableBridge $bridge, \MUtil\Model\ModelAbstract $model)
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
     * @return \Gems\Menu\SubMenuItem
     */
    protected function findMenuItem($controller, $action = 'index')
    {
        return $this->menu->find(array('controller' => $controller, 'action' => $action, 'allowed' => true));
    }

    /**
     * Returns an edit menu item, if access is allowed by privileges
     *
     * @return \Gems\Menu\SubMenuItem
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
     * @return \MUtil\Html\HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view)
    {
        if ($table = parent::getHtmlOutput($view)) {
            if ($title = $this->getTitle()) {
                $htmlDiv = \MUtil\Html::div(array('renderWithoutContent' => false));

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
     * @param \MUtil\Model\Bridge\VerticalTableBridge $bridge
     * @param \MUtil\Model\ModelAbstract $model
     * @return void
     */
    protected function setShowTableFooter(\MUtil\Model\Bridge\VerticalTableBridge $bridge, \MUtil\Model\ModelAbstract $model)
    {
        /*if ($this->displayMenu) {
            if (! $this->menuList) {
                $this->menuList = $this->menu->getCurrentMenuList($this->request, $this->_('Cancel'));
            }
            if ($this->menuList instanceof \Gems\Menu\MenuList) {
                $this->menuList->addParameterSources($bridge);
            }

            $bridge->tfrow($this->menuList, array('class' => 'centerAlign'));
        }*/
    }
}
