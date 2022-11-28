<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker\Buttons
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
 * @subpackage Tracker\Buttons
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 9-sep-2015 19:07:12
 */
class NewFieldButtonRow extends CurrentButtonRowSnippet
{
    protected $trackId;

    /**
     * Set the menu items (allows for overruling in subclasses)
     */
    protected function addButtons(): array
    {
        $menuList = [];

        $matchedParams = $this->requestInfo->getRequestMatchedParams();

        $route = $this->menuHelper->getRouteUrl('track-builder.track-maintenance.track-fields.create', $matchedParams);

        $menuList[] = [
            'label' => $this->_('New field'),
            'url' =>$route,
        ];

        return $menuList;
    }
}
