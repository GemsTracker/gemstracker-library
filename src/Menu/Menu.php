<?php

namespace Gems\Menu;

use Gems\User\User;
use Mezzio\Router\RouteResult;
use Mezzio\Template\TemplateRendererInterface;

class Menu extends MenuNode
{
    protected bool $horizontal = false;

    /** @var MenuItem[] */
    private array $items = [];

    public function __construct(
        public readonly TemplateRendererInterface $templateRenderer,
        public readonly RouteHelper $routeHelper,
        array $menuConfig,
        private readonly User|null $user = null,
    ) {
        $this->addFromConfig($this, $menuConfig);
    }

    public function addFromConfig(MenuNode $node, array $items)
    {
        foreach ($items as $item) {
            $object = match($item['type']) {
                'route', 'route-link-item' => new RouteLinkItem($item['name'], $item['label']),
                'container' => new ContainerLinkItem($item['name'], $item['label']),
                'alias' => new AliasItem($item['name'], $item['alias']),
                'logged-out-route' => new LoggedOutRouteItem($item['name'], $item['label'], $this->user !== null),
                default => throw new \Exception('Invalid type: ' . $item['type']),
            };

            if (isset($item['parent'])) {
                $parent = $node->getMenu()->find($item['parent']);
            } else {
                $parent = $node;
            }

            $position = $item['position'] ?? null;

            $parent->add($object, $position);

            if (!empty($item['children'])) {
                $this->addFromConfig($object, $item['children']);
            }
        }
    }

    public function find(string $name): MenuItem
    {
        return $this->items[$name] ?? throw new MenuItemNotFoundException($name);
    }

    /**
     * @param string|null $prefix
     * @param MenuNode[] $children
     * @return string[][]
     */
    private function gatherRouteLabelsByPrivilege(?string $prefix, array $children): array
    {
        return array_reduce($children, function (array $carry, MenuItem $child) use ($prefix) {
            $route = $this->routeHelper->getUncheckedRoute($child->name);

            $newPrefix = ($prefix ? $prefix . ' -> ' : '') . ($child->getLabel() ?: $route['name']);

            if (isset($route['options']['privilege'])) {
                $carry[$route['options']['privilege']][] = $newPrefix;
            }

            return array_merge($carry, $this->gatherRouteLabelsByPrivilege($newPrefix, $child->children));
        }, []);
    }

    protected function getMenu(): Menu
    {
        return $this;
    }

    /**
     * @return string[][]
     */
    public function getRouteLabelsByPrivilege(): array
    {
        return $this->gatherRouteLabelsByPrivilege(null, $this->children);
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function isHorizontal(): bool
    {
        return $this->horizontal;
    }

    /**
     * Make the menu item for this route and its parent items active.
     * If the menu item is an alias, we open the aliased path instead.
     */
    public function openRouteResult(RouteResult $routeResult): void
    {
        $name = $routeResult->getMatchedRouteName();

        if (!isset($this->items[$name])) {
            return;
        }

        $item = $this->items[$name];
        if ($item instanceof AliasItem) {
            $alias = $item->alias;
            if (isset($this->items[$alias])) {
                $item = $this->items[$alias];
            }
        }
        $item->openPath($routeResult->getMatchedParams());
    }

    public function registerItem(string $name, MenuItem $menuItem)
    {
        $this->items[$name] = $menuItem;
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

    public function renderMainRow(): string
    {
        foreach ($this->children as $child) {
            $child->open([]);
        }

        return $this->templateRenderer->render('menu::main-menu-row', [
            'children' => $this->children,
        ]);
    }

    public function renderMenu(): string
    {
        if ($this->isHorizontal()) {
            foreach ($this->children as $child) {
                if ($child->isActive()) {
                    return $this->templateRenderer->render('menu::main-menu', [
                        'children' => $child->children,
                    ]);
                }
            }

        }
        return $this->renderNode();
    }

    public function setHorizontal(bool $horizontal): void
    {
        $this->horizontal = $horizontal;
    }
}
