<?php


namespace Gems\Event\Application;


use Symfony\Component\EventDispatcher\Event;

class NamedArrayEvent extends Event
{
    protected $list;

    public function __construct($list = [])
    {
        $this->list = $list;
    }

    public function addItem($key, $value)
    {
        $this->list[$key] = $value;
    }

    public function addItems($items)
    {
        $this->list = array_merge($this->list, $items);
    }

    public function getList()
    {
        return $this->list;
    }
}
