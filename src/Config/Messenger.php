<?php

namespace Gems\Config;

use Doctrine\ORM\EntityManagerInterface;
use Gems\Messenger\Message\CommJob;
use Gems\Messenger\Message\SendCommJobMessage;
use Gems\Messenger\Message\SendTokenMessage;
use Gems\Messenger\Message\SetCommJobTokenAsSent;
use Gems\Messenger\Message\TokenResponse;
use Symfony\Component\Mailer\Messenger\MessageHandler;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;

class Messenger
{
    public function __invoke()
    {
        return [
            'buses' => [
                'messenger.bus.default' => [
                    'middleware' => [],
                    'routes' => [
                        CommJob::class => 'messenger.transport.default',
                        SendCommJobMessage::class => 'messenger.transport.default',
                        SendTokenMessage::class => 'messenger.transport.default',
                        SetCommJobTokenAsSent::class => 'messenger.transport.default',
                        TokenResponse::class => 'messenger.transport.default',
                    ],
                    'handlers' => [
                        SendEmailMessage::class => MessageHandler::class,
                    ],
                ],
                'event.bus' => [
                    'allows_zero_handlers' => true,
                ]
            ],
            'transports' => [
                'messenger.transport.default' => [
                    'dsn' => 'sync://',
                    'messengerBus' => 'messenger.bus.default',
                ],
                'messenger.transport.doctrine' => [
                    'dsn' => 'doctrine://default',
                    'entityManager' => EntityManagerInterface::class,
                ],
                /*'messenger.transport.redis' => [
                    'dsn' => 'redis://localhost:6379/messages',
                ],
                'messenger.transport.test' => [
                    'dsn' => 'in-memory://',
                ],
                 */
            ],
        ];
    }
}
