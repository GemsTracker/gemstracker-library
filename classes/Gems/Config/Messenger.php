<?php

namespace Gems\Config;

use Gems\Messenger\MessengerFactory;
use Symfony\Component\Messenger\MessageBusInterface;

class Messenger
{
    public function __invoke()
    {
        return [
            'buses' => [
                'messenger.bus.default' => [
                    'middleware' => [],
                    'routes' => [],
                    'handlers' => [],
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
                /* 'messenger.transport.doctrine' => [
                    'dsn' => 'doctrine://default',
                    'entityManager' => EntityManagerInterface:: class,
                ],
                'messenger.transport.redis' => [
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