<?php

namespace Gems\MenuNew;

use Mezzio\Router\RouteResult;
use Mezzio\Template\TemplateRendererInterface;

class Menu extends MenuNode
{
    /** @var MenuItem[] */
    private array $items = [];

    public function __construct(
        public readonly TemplateRendererInterface $templateRenderer,
        public readonly RouteHelper $routeHelper,
        \Gems\Config\Menu $menuConfig,
    ) {
        $this->addFromConfig($this, $menuConfig->getItems());
    }

    private function addFromConfig(MenuNode $node, array $items)
    {
        foreach ($items as $item) {
            $object = match($item['type']) {
                'route-link-item' => new RouteLinkItem($item['name'], $item['label']),
                'container' => new ContainerLinkItem($item['name'], $item['label']),
                default => throw new \Exception('Invalid type: ' . $item['type']),
            };

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

    public function renderNode(): string
    {
        foreach ($this->children as $child) {
            $child->open([]);
        }

        return $this->templateRenderer->render('menu::main-menu', [
            'children' => $this->children,
        ]);
    }

    public function renderMenu(): string
    {
        return $this->renderNode();
    }

    /**
     * @return string[][]
     */
    public function getRouteLabelsByPrivilege(): array
    {
        return $this->gatherRouteLabelsByPrivilege(null, $this->children);
    }

    /**
     * @param string|null $prefix
     * @param MenuNode[] $children
     * @return string[][]
     */
    private function gatherRouteLabelsByPrivilege(?string $prefix, array $children): array
    {
        return array_reduce($children, function (array $carry, MenuItem $child) use ($prefix) {
            $route = $this->routeHelper->getRoute($child->name);

            $newPrefix = ($prefix ? $prefix . ' -> ' : '') . $child->getLabel();

            if (isset($route['options']['privilege'])) {
                $carry[$route['options']['privilege']][] = $newPrefix;
            }

            return array_merge($carry, $this->gatherRouteLabelsByPrivilege($newPrefix, $child->children));
        }, []);
    }
}
