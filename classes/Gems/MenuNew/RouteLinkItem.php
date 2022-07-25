<?php

namespace Gems\MenuNew;

class RouteLinkItem extends MenuItem
{
    private ?array $openParams = null;

    public function __construct(
        public readonly string $name,
        private readonly string $label,
    ) {
    }

    protected function register()
    {
        $this->getMenu()->registerItem($this->name, $this);
    }

    protected function hasPermission(): bool
    {
        $route = $this->getMenu()->getRoute($this->name);
        if (empty($route['options']['permission'])) {
            return true;
        }

        return $this->getMenu()->isAllowed($route['options']['permission']);
    }

    public function open(array $params): bool
    {
        $route = $this->getMenu()->getRoute($this->name);
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
        $menu = $this->getMenu();

        $url = $menu->router->generateUri($this->name, $this->openParams);

        return $menu->templateRenderer->render('menu::route-link-item', [
            'url' => $url,
            'label' => $this->label,
            'active' => $this->isActive(),
        ]);
    }
}
