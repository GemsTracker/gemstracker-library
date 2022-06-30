<?php

namespace Gems\MenuNew;

use Mezzio\Router\RouteResult;
use Mezzio\Router\RouterInterface;
use Mezzio\Template\TemplateRendererInterface;

class Menu extends MenuNode
{
    /** @var MenuItem[] */
    private array $items = [];

    public function __construct(
        public readonly RouterInterface $router,
        public readonly TemplateRendererInterface $templateRenderer,
    ) {
    }

    protected function getMenu(): Menu
    {
        return $this;
    }

    public function registerItem(string $name, MenuItem $menuItem)
    {
        $this->items[$name] = $menuItem;
    }

    public function find(string $name): MenuItem
    {
        return $this->items[$name] ?? throw new MenuItemNotFoundException($name);
    }

    public function openRouteResult(RouteResult $routeResult): void
    {
        $name = $routeResult->getMatchedRouteName();

        if (!isset($this->items[$name])) {
            return;
        }

        $item = $this->items[$name];
        $item->openPath($routeResult->getMatchedParams());
    }

    public function renderContent(): string
    {
        return '';
    }

    public function renderMenu(): string
    {
        foreach ($this->children as $child) {
            $child->open([]);
        }
        return $this->renderNode();
    }
}
