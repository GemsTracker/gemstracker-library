<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Tracker\Buttons;

use Gems\Snippets\Generic\CurrentButtonRowSnippet;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 8-mei-2015 13:46:43
 */
class TrackActionButtonRow extends CurrentButtonRowSnippet
{
    /**
     * Add the children of the current menu item
     *
     * @var boolean
     */
    protected bool $addCurrentChildren = true;

    /**
     * Add the parent of the current menu item
     *
     * @var boolean
     */
    protected bool $addCurrentParent = false;

    /**
     * Add the siblings of the current menu item
     *
     * @var boolean
     */
    protected bool $addCurrentSiblings = true;

    /**
     * Set the menu items (allows for overruling in subclasses)
     */
    protected function getButtons(): array
    {
        $this->extraRoutesLabelled = [
            'respondent.show'              => $this->_('Show respondent'),
            'respondent.edit'              => $this->_('Edit respondent'),
            'respondent.tracks.index'      => $this->_('Show tracks'),
            'respondent.tracks.edit-track' => $this->_('Edit track'),
        ];

        return parent::getButtons();
    }
}
