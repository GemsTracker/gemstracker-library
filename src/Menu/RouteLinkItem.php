<?php

namespace Gems\Menu;

class RouteLinkItem extends MenuItem
{
    protected ?array $openParams = null;

    protected string $itemTemplate = 'menu::route-link-item';

    public function __construct(
        public readonly string $name,
        public string $label,
    ) {
    }

    protected function register()
    {
        $this->getMenu()->registerItem($this->name, $this);
    }

    protected function hasAccess(): bool
    {
        return $this->getMenu()->routeHelper->hasAccessToRoute($this->name);
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function open(array $params): bool
    {
        $route = $this->getMenu()->routeHelper->getRoute($this->name);
        $requiredParams = $route['params'] ?? [];

        $missingParams = array_diff($requiredParams, array_keys($params));

        if (count($missingParams) > 0) {
            return false;
        }

        $params = array_intersect_key($params, array_flip($requiredParams));

        if (!parent::open($params)) {
            return false;
        }

        $this->openParams = $params;
        return true;
    }

    public function renderNode(): string
    {
        if (!$this->isOpen()) {
            return '';
        }

        $menu = $this->getMenu();
        $url = $menu->routeHelper->getRouteUrl($this->name, $this->openParams);

        $children = [];
        foreach ($this->children as $child) {
            if ($child->isOpen()) {
                $children[] = $child;
            }
        }

        return $this->getMenu()->templateRenderer->render($this->itemTemplate, [
            'url' => $url,
            'label' => $this->label,
            'active' => $this->isActive(),
            'children' => $children,
        ]);
    }

    public function setLabel(string $label): void
    {
        $this->label = $label;
    }
}
