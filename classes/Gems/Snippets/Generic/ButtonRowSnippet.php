<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Generic
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: CurrentSiblingsButtonRowSnippet.php 203 2011-07-07 12:51:32Z matijs $
 */

namespace Gems\Snippets\Generic;

/**
 * Displays the parent menu item (if existing) plus any current
 * level buttons that are visible
 *
 * @package    Gems
 * @subpackage Snippets\Generic
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2
 */
class ButtonRowSnippet extends \MUtil_Snippets_SnippetAbstract
{
    /**
     * Add the children of the current menu item
     *
     * @var boolean
     */
    protected $addCurrentChildren = false;

    /**
     * Add the parent of the current menu item
     *
     * @var boolean
     */
    protected $addCurrentParent = false;

    /**
     * Add the siblings of the current menu item
     *
     * @var boolean
     */
    protected $addCurrentSiblings = false;

    /**
     * Add siblings of the current menu item with any parameters.
     *
     * Add only those with the same when false.
     *
     * @var boolean
     */
    protected $anyParameterSiblings = false;

    /**
     * Required
     *
     * @var \Gems_Menu
     */
    protected $menu;

    /**
     * Required
     *
     * @var \Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     * Set the menu items (allows for overruling in subclasses)
     *
     * @param \Gems_Menu_MenuList $menuList
     */
    protected function addButtons(\Gems_Menu_MenuList $menuList)
    {
        if ($this->addCurrentParent) {
            $menuList->addCurrentParent($this->_('Cancel'));
        }
        if ($this->addCurrentSiblings) {
            $menuList->addCurrentSiblings($this->anyParameterSiblings);
        }
        if ($this->addCurrentChildren) {
            $menuList->addCurrentChildren();
        }
        // \MUtil_Echo::track($this->addCurrentParent, $this->addCurrentSiblings, $this->addCurrentChildren, count($menuList));
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
        /*$menuList = $this->menu->getMenuList();

        $menuList->addParameterSources($this->request, $this->menu->getParameterSource());

        // \MUtil_Echo::track($this->request->getParams(), $this->menu->getParameterSource()->getArrayCopy());

        $this->addButtons($menuList);

        if ($menuList->render($view)) {
            return \MUtil_Html::create('div', array('class' => 'buttons', 'renderClosingTag' => true), $menuList);
        }*/
    }
}
