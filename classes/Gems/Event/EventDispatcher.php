<?php

namespace Gems\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Wrapper around the 3.4 Symfony Event, that changes the order of Dispatch parameters,
 * as this has been changed in Symfony 4.3 to follow PSR-14
 *
 * Class EventDispatcher
 * @package Gems\Event
 * @method void addListener(string $eventName, callable $listener, int $priority=0) Adds an event listener that listens on the specified events.
 * @method void addSubscriber(EventSubscriberInterface $subscriber) Adds an event subscriber.
 * @method void removeListener(string $eventName, callable $listener) Adds an event listener that listens on the specified events.
 * @method void removeSubscriber(EventSubscriberInterface $subscriber) Adds an event subscriber.
 * @method array getListeners(string|null $eventName) Gets the listeners of a specific event or all listeners sorted by descending priority.
 * @method int|null getListenerPriority(string $eventName, callable $listener) Gets the listener priority for a specific event.
 * @method boolean hasListeners(string|null $eventName) Checks whether an event has any registered listeners.
 */

class EventDispatcher
{
    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcher
     */
    protected $eventDispatcher;

    protected $legacyDispatcher = false;

    public function __construct()
    {
        $this->eventDispatcher = new \Symfony\Component\EventDispatcher\EventDispatcher();
        if (!in_array('Psr\EventDispatcher\EventDispatcherInterface', class_implements($this->eventDispatcher))) {
            $this->legacyDispatcher = true;
        }
    }

    public function dispatch(Event $event, $eventName = null)
    {
        if (!$this->legacyDispatcher) {
            return $this->eventDispatcher->dispatch($event, $eventName);
        }

        if (is_null($eventName)) {
            $eventName = \get_class($event);
        }

        return $this->eventDispatcher->dispatch($eventName, $event);
    }

    public function __call($name, $args)
    {
        if (method_exists($this->eventDispatcher, $name)) {
            return call_user_func_array([$this->eventDispatcher, $name], $args);
        }
    }
}
