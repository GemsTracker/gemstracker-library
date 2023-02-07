<?php

namespace Gems\MenuNew;

class ContainerLinkItem extends MenuItem
{
    private ?array $openParams = null;

    public function __construct(
        public readonly string $name,
        public readonly string $label,
    ) {
    }

    protected function register()
    {
        $this->getMenu()->registerItem($this->name, $this);
    }

    protected function hasAccess(): bool
    {
        foreach($this->children as $child) {
            $access = $this->getMenu()->routeHelper->hasAccessToRoute($child->name);
            if ($access) {
                return true;
            }
        }

        return false;
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

    public function renderContent(): string
    {
        foreach ($this->children as $child) {
            $child->open([]);
        }

        $menu = $this->getMenu();

        $url = $menu->routeHelper->getRouteUrl($this->name, $this->openParams);

        $children = [];
        foreach ($this->children as $child) {
            if ($child->isOpen()) {
                $children[] = $child;
            }
        }

        return $menu->templateRenderer->render('menu::container-link-item', [
            'url' => $url,
            'label' => $this->label,
            'active' => $this->isActive(),
            'children' => $children,
        ]);
    }
}
