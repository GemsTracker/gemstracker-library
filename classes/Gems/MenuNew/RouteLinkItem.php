<?php

namespace Gems\MenuNew;

class RouteLinkItem extends MenuItem
{
    private ?array $openParams = null;

    public function __construct(
        private readonly string $name,
        private readonly string $label,
    ) {
    }

    protected function register()
    {
        $this->getMenu()->registerItem($this->name, $this);
    }

    public function open(array $params): void
    {
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
        ]);
    }
}
