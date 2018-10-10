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
 * Parent class for snippets that need to do something with the \Gems_Menu
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
abstract class Gems_Snippets_MenuSnippetAbstract extends \MUtil_Snippets_SnippetAbstract
{
    /**
     *
     * @var \Gems_Menu
     */
    public $menu;

    /**
     *
     * @param string $controller
     * @param string $action
     * @return \Gems_Menu_SubMenuItem
     */
    public function findMenuItem($controller, $action = 'index')
    {
        return $this->menu->find(array('controller' => $controller, 'action' => $action, 'allowed' => true));
    }
}
