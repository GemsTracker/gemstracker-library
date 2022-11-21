<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage MenuNew
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\MenuNew;

use Zalt\Base\RequestInfo;

/**
 *
 * @package    Gems
 * @subpackage MenuNew
 * @since      Class available since version 1.9.2
 */
class MenuUrlRouter implements \Zalt\Html\Routes\UrlRouterInterface
{
    public function __construct(
        protected Menu $menu,
        protected RouteHelper $routeHelper,
        protected RequestInfo $requestInfo
    )
    { }
    
    public function getChildRoutes(string $parent, array $params): array
    {
        $output = [];
        
        try {
            $menuItem = $this->menu->find($parent);
        } catch (MenuItemNotFoundException $minfe) {
            return $output;
        }
        
        foreach ($menuItem->getChildren() as $child) {
            if ($child instanceof RouteLinkItem) {
                $child->openPath($params);
                
                if ($child->isOpen()) {
                    $url = $this->routeHelper->getRouteUrl($child->name, $params);
                    $output[$child->label] = $url; 
                }
            }            
        }
        
        return $output;
    }

    public function getCurrentparams() : array
    {
        return $this->requestInfo->getParams();
    }
    
    public function getCurrentRoute() : string
    {
        return $this->requestInfo->getRouteName();
    }

    public function getParentUrl(string $currentRoute, array $params): ?string
    {
        try {
            $menuItem = $this->menu->find($currentRoute);

            $parent = $menuItem->getParent();
            if ($parent instanceof RouteLinkItem) {
                return $this->routeHelper->getRouteUrl($parent->name, $params);
            }
        } catch (MenuItemNotFoundException $minfe) {
            return null;
        }


        return null;
    }

    public function getParentRoutes(string $currentRoute, array $params, int $for = 1): array
    {
        $output = [];

        try {
            $menuItem = $this->menu->find($currentRoute);
        } catch (MenuItemNotFoundException $minfe) {
            return $output;
        }

        while (--$for >= 0) {
            $parent = $menuItem->getParent();
            
            if ($parent instanceof RouteLinkItem) {
                $url = $this->routeHelper->getRouteUrl($parent->name, $params);
                $output[$parent->label] = $url; 
            } else {
                break;
            }
        }
        return $output;
    }
}