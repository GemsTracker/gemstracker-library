<?php

namespace GemsTest\testUtils;

use Symfony\Component\Mailer\Event\SentMessageEvent;

class SentMailListener
{
    public function __construct(
        protected int $sentMessageCount = 0,
        protected array $sentMessages = [],
    )
    {}

    public function __invoke(SentMessageEvent $event): void
    {
        $this->sentMessageCount++;

        $this->sentMessages[] = $event->getMessage();
    }

    public function getSentMessageCount(): int
    {
        return $this->sentMessageCount;
    }
}