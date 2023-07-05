<?php

namespace Gems\Menu;

use Gems\Event\Application\CreateMenuEvent;
use Gems\Event\Application\MenuBuildItemsEvent;
use Mezzio\Template\TemplateRendererInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class MenuRepository
{
    private Menu|null $menu = null;

    private array|null $menuConfig = null;

    public function __construct(
        private readonly TemplateRendererInterface $templateRenderer,
        private readonly RouteHelper $routeHelper,
        private readonly TranslatorInterface $translator,
        private readonly EventDispatcherInterface $eventDispatcher,
    )
    {}

    public function getMenu(): Menu
    {
        if (!$this->menu instanceof Menu) {
            $menu = new Menu($this->templateRenderer, $this->routeHelper, $this->getMenuConfig());

            $event = new CreateMenuEvent($menu);
            $this->eventDispatcher->dispatch($event);

            $this->menu = $menu;
        }

        return $this->menu;
    }

    public function getMenuConfig(): array
    {
        if ($this->menuConfig === null) {
            $menuConfig = new \Gems\Config\Menu($this->translator);
            $items = $menuConfig->getItems();

            $event = new MenuBuildItemsEvent($items);
            $this->eventDispatcher->dispatch($event);

            $this->menuConfig = $event->getItems();
        }

        return $this->menuConfig;
    }
}