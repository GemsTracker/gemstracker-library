<?php


namespace Gems\Event\Application;


trait NamedArrayEventTrait
{
    protected $list;

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
