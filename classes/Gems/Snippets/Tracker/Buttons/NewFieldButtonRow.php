<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker\Buttons
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: NewFieldButtonRow.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

namespace Gems\Snippets\Tracker\Buttons;

use Gems\Snippets\Generic\CurrentButtonRowSnippet;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker\Buttons
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 9-sep-2015 19:07:12
 */
class NewFieldButtonRow extends CurrentButtonRowSnippet
{
    /**
     * Set the menu items (allows for overruling in subclasses)
     *
     * @param \Gems_Menu_MenuList $menuList
     */
    protected function addButtons(\Gems_Menu_MenuList $menuList)
    {
        $menuList->addByController('track-fields', 'create', $this->_('New field'));
    }
}
