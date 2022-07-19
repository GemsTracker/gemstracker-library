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
            ],
            'transports' => [
                'test' => [
                    'dsn' => 'in-memory://',
                    'serialize' => false,
                ],
                /* 'messenger.transport.doctrine' => [
                    'dsn' => 'doctrine://default',
                    'entityManager' => EntityManagerInterface:: class,
                ],
                'messenger.transport.redis' => [
                    'dsn' => 'redis://localhost:6379/messages',
                ], */
            ],
        ];
    }
}