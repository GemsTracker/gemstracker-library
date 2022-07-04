<?php


namespace Gems\Event\Application;


use Symfony\Contracts\EventDispatcher\Event;

class MenuAdd extends Event
{
    use SymfonyTranslationEventTrait;

    const NAME = 'gems.menu.add';
    /**
     * @var \Gems_Menu
     */
    protected $menu;

    public function __construct(\Gems_Menu $menu)
    {
        $this->menu = $menu;
    }

    /**
     * @return \Gems_Menu
     */
    public function getMenu()
    {
        return $this->menu;
    }
}
