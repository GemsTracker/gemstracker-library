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
 * Parent class for snippets that need to do something with the \Gems\Menu
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
abstract class MenuSnippetAbstract extends \MUtil\Snippets\SnippetAbstract
{
    /**
     *
     * @var \Gems\Menu
     */
    public $menu;

    /**
     *
     * @param string $controller
     * @param string $action
     * @return \Gems\Menu\SubMenuItem
     */
    public function findMenuItem($controller, $action = 'index')
    {
        return $this->menu->find(array('controller' => $controller, 'action' => $action, 'allowed' => true));
    }
}
