<?php


namespace Gems\Event\Application;


use Symfony\Component\EventDispatcher\Event;

class MenuAdd extends Event
{
    use Zend1TranslatableEventTrait;

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
