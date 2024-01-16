<?php

namespace Gems\Menu;

use Gems\Event\Application\CreateMenuEvent;
use Gems\Event\Application\MenuBuildItemsEvent;
use Gems\Legacy\CurrentUserRepository;
use Gems\User\User;
use Laminas\Permissions\Acl\Acl;
use Mezzio\Helper\UrlHelper;
use Mezzio\Template\TemplateRendererInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Zalt\Base\TranslatorInterface;

class MenuRepository
{
    private Menu|null $menu = null;

    private array|null $menuConfig = null;

    public function __construct(
        private readonly TemplateRendererInterface $templateRenderer,
        private readonly Acl $acl,
        private readonly CurrentUserRepository $currentUserRepository,
        private readonly UrlHelper $urlHelper,
        private readonly TranslatorInterface $translator,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly array $config,
    )
    {}

    public function getMenu(): Menu
    {
        if (!$this->menu instanceof Menu) {
            $routeHelper = $this->getRouteHelper();

            $menu = new Menu($this->templateRenderer, $routeHelper, $this->getMenuConfig(), $this->currentUserRepository->getCurrentUser());

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

    protected function getRouteHelper(): RouteHelper
    {
        return new RouteHelper($this->acl, $this->urlHelper, $this->currentUserRepository, $this->config);
    }
}