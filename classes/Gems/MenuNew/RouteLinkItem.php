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

    public function open(array $params): void
    {
        $route = $this->getMenu()->getRoute($this->name);
        $requiredParams = $route['params'] ?? [];

        $missingParams = array_diff($requiredParams, array_keys($params));

        if (count($missingParams) > 0) {
            return;
        }

        $params = array_intersect_key($params, array_flip($requiredParams));

        parent::open($params);

        $this->openParams = $params;
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
