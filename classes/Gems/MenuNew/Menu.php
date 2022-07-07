<?php

namespace Gems\MenuNew;

use Mezzio\Router\RouteResult;
use Mezzio\Router\RouterInterface;
use Mezzio\Template\TemplateRendererInterface;

class Menu extends MenuNode
{
    /** @var MenuItem[] */
    private array $items = [];

    private array $routes;

    public function __construct(
        public readonly RouterInterface $router,
        public readonly TemplateRendererInterface $templateRenderer,
        array $config,
    ) {
        $this->routes = [];
        foreach ($config['routes'] as $route) {
            $this->routes[$route['name']] = $route;
        }

        $this->addFromConfig($this, $config['menu']);
    }

    public function getRoute(string $name): array
    {
        return $this->routes[$name];
    }

    private function addFromConfig(MenuNode $node, array $items)
    {
        foreach ($items as $item) {
            if ($item['type'] === 'route-link-item') {
                $object = new RouteLinkItem($item['name'], $item['label']);
            } else {
                throw new \Exception('Invalid type: ' . $item['type']);
            }

            if (isset($item['parent'])) {
                $parent = $node->getMenu()->find($item['parent']);
            } else {
                $parent = $node;
            }

            $parent->add($object);

            if (!empty($item['children'])) {
                $this->addFromConfig($object, $item['children']);
            }
        }
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
