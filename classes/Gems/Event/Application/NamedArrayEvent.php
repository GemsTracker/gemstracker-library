<?php


namespace Gems\Event\Application;


use Symfony\Component\EventDispatcher\Event;

class NamedArrayEvent extends Event
{
    use NamedArrayEventTrait;

    protected $list;

    public function __construct($list = [])
    {
        $this->list = $list;
    }
}
