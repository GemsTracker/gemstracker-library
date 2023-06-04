<?php


namespace Gems\Event\Application;


use Symfony\Contracts\EventDispatcher\Event;

class NamedArrayEvent extends Event
{
    use NamedArrayEventTrait;

    //protected $list;

    public function __construct($list = [])
    {
        $this->list = $list;
    }
}
