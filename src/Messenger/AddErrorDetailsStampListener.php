<?php

declare(strict_types=1);

namespace Gems\Messenger;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;

// Same as Symfony, but with Stamp that adds stack trace
final class AddErrorDetailsStampListener implements EventSubscriberInterface
{
    public function onMessageFailed(WorkerMessageFailedEvent $event): void
    {
        $stamp = ErrorDetailsStamp::create($event->getThrowable());
        $previousStamp = $event->getEnvelope()->last(ErrorDetailsStamp::class);

        // Do not append duplicate information
        if (null === $previousStamp || !$previousStamp->equals($stamp)) {
            $event->addStamps($stamp);
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // must have higher priority than SendFailedMessageForRetryListener
            WorkerMessageFailedEvent::class => ['onMessageFailed', 200],
        ];
    }
}