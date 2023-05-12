<?php

namespace Gems\Messenger\Handler;

use Gems\Event\Application\TokenMarkedAsSent;
use Gems\Messenger\Message\SetCommJobTokenAsSent;
use Gems\Repository\CommJobRepository;
use Gems\Tracker;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class SetCommJobTokenAsSentHandler
{
    public function __construct(
        protected Tracker $tracker,
        protected CommJobRepository $commJobRepository,
        protected EventDispatcherInterface $eventDispatcher,
    )
    {}

    public function __invoke(SetCommJobTokenAsSent $message): void
    {
        $token = $this->tracker->getToken($message->getTokenId());

        $jobData = $this->commJobRepository->getJob($message->getJobId());

        $event = new TokenMarkedAsSent($token, $jobData);

        $this->eventDispatcher->dispatch($event, TokenMarkedAsSent::NAME);
    }
}