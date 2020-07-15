<?php


namespace Gems;


use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EventSubscriber implements EventSubscriberInterface
{

    public static function getSubscribedEvents()
    {
        return [];
    }
}
