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
class CurrentSiblingsButtonRowSnippet extends ButtonRowSnippet
{
    /**
     * Add the children of the current menu item
     *
     * @var boolean
     */
    protected $addCurrentChildren = true;

    /**
     * Add the parent of the current menu item
     *
     * @var boolean
     */
    protected $addCurrentParent = true;

    /**
     * Add the siblings of the current menu item
     *
     * @var boolean
     */
    protected $addCurrentSiblings = true;
}
