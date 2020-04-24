<?php

namespace Gems\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Wrapper around the 3.4 Symfony Event, that changes the order of Dispatch parameters,
 * as this has been changed in Symfony 4.3 to follow PSR-14
 *
 * Class EventDispatcher
 * @package Gems\Event
 */

class EventDispatcher
{
    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcher
     */
    protected $eventDispatcher;

    public function __construct()
    {
        $this->eventDispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();
    }

    public function dispatch(Event $event, $eventName = null)
    {
        $eventName = $eventName ?? \get_class($event);

        return $this->eventDispatcher->dispatch($eventName, $event);
    }

    public function __call($name, $args)
    {
        if (method_exists($this->eventDispatcher, $name)) {
            return call_user_func_array([$this->eventDispatcher, $name], $args);
        }
    }
}
