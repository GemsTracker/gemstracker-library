<?php

namespace Gems\Event;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\StoppableEventInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 *  Load Event Listeners through psr-11 container
 */
class Psr11EventDispatcher extends EventDispatcher
{
    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct();
    }

    /**
     * If supplied listener name is a class, register that as Listener, instead of a callable from the subscriber
     *
     * @param EventSubscriberInterface $subscriber
     * @return void
     */
    public function addSubscriber(EventSubscriberInterface $subscriber)
    {
        foreach ($subscriber->getSubscribedEvents() as $eventName => $params) {
            if (\is_string($params)) {
                $this->addListener($eventName, $this->getListenerFromSubscriber($subscriber, $params));
            } elseif (is_array($params) && !empty($params) && \is_string($params[0])) {
                $this->addListener($eventName, $this->getListenerFromSubscriber($subscriber, $params[0]), $params[1] ?? 0);
            } else {
                foreach ($params as $listener) {
                    if (!isset($listener[0])) {
                        $test = true;
                    }
                    $this->addListener($eventName, $this->getListenerFromSubscriber($subscriber, $listener[0]), $listener[1] ?? 0);
                }
            }
        }
    }

    /**
     * If listener is a class, load it through the psr-11 container
     */
    protected function callListeners(iterable $listeners, string $eventName, object $event)
    {
        $stoppable = $event instanceof StoppableEventInterface;

        foreach ($listeners as $listener) {
            if ($stoppable && $event->isPropagationStopped()) {
                break;
            }
            if (is_string($listener) && class_exists($listener)) {
                $listener = $this->container->get($listener);
            }
            $listener($event, $eventName, $this);
        }
    }

    /**
     * If supplied listener name is a class, register that as Listener, instead of a callable from the subscriber
     *
     * @param EventSubscriberInterface $eventSubscriber
     * @param string $listener
     * @return callable
     */
    protected function getListenerFromSubscriber(EventSubscriberInterface $eventSubscriber, string $listener): callable
    {
        if (class_exists($listener)) {
            return $listener;
        }
        return [$eventSubscriber, $listener];
    }
}