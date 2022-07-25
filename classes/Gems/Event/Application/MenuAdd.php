<?php


namespace Gems\Event\Application;


use Symfony\Contracts\EventDispatcher\Event;

class MenuAdd extends Event
{
    use SymfonyTranslationEventTrait;

    const NAME = 'gems.menu.add';
    /**
     * @var \Gems\Menu
     */
    protected $menu;

    public function __construct(\Gems\Menu $menu)
    {
        $this->menu = $menu;
    }

    /**
     * @return \Gems\Menu
     */
    public function getMenu()
    {
        return $this->menu;
    }
}
