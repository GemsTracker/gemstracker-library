<?php

namespace Gems\Event\Application;

use Symfony\Contracts\EventDispatcher\Event;

class MenuBuildItemsEvent extends Event
{
    public const NAME = 'menu.build.items';

    public function __construct(private array $items)
    {
    }

    public function addItems(array $items): void
    {
        $this->items = array_merge($this->items, $items);
    }

    public function getItems(): array
    {
        return $this->items;
    }
}
