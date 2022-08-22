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
     *
     * @param \Gems\Menu\MenuList $menuList
     */
    protected function addButtons(array $menuList): array
    {
        $route = $this->routeHelper->getRoute('track-builder.track-maintenance.track-fields.create');

        $matchedParams = $this->requestInfo->getCurrentRouteResult()->getMatchedParams();

        if ($this->trackId) {
            $matchedParams['trackId'] = (int)$this->trackId;
        }

        $params = $this->routeHelper->getRouteParamsFromKnownParams($route, $matchedParams);

        $menuList[] = [
            'label' => $this->_('New field'),
            'url' => $this->routeHelper->getRouteUrl($route['name'], $params),
        ];

        return $menuList;
    }
}
