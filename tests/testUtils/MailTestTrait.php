<?php

namespace GemsTest\testUtils;

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Mailer\Event\SentMessageEvent;

trait MailTestTrait
{
    protected SentMailListener $sentMailListener;
    protected function initMailTests(): void
    {
        $this->sentMailListener = new SentMailListener();
        /**
         * @var EventDispatcher $eventDispatcher
         */
        $eventDispatcher = $this->container->get(EventDispatcherInterface::class);
        $eventDispatcher->addListener(SentMessageEvent::class, $this->sentMailListener);
    }

    public function assertNumberOfMailsSent(int $expected, string $message = ''): void
    {
        static::assertEquals($expected, $this->sentMailListener->getSentMessageCount(), $message);
    }
}