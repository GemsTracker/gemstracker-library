<?php

namespace Gems\Event\Application;

use Gems\Menu\Menu;
use Symfony\Contracts\EventDispatcher\Event;

class CreateMenuEvent extends Event
{
    public function __construct(
        private Menu $menu,
    )
    {}

    /**
     * @return Menu
     */
    public function getMenu(): Menu
    {
        return $this->menu;
    }
}