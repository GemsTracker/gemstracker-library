<?php

namespace Gems\Dev;

use Clockwork\Clockwork;
use Gems\Communication\Http\DevMailSmsClient;
use Gems\Communication\Http\SmsClientInterface;
use Gems\Dev\Clockwork\Factory\ClockworkFactory;
use Gems\Dev\Clockwork\Handlers\ClockworkApiHandler;
use Gems\Dev\Clockwork\Middleware\ClockworkMiddleware;
use Gems\Dev\Middleware\DebugDumperMiddleware;
use Gems\Middleware\SecurityHeadersMiddleware;
use Laminas\ConfigAggregator\ConfigAggregator;
use Mezzio\Container\WhoopsErrorResponseGeneratorFactory;
use Mezzio\Container\WhoopsFactory;
use Mezzio\Container\WhoopsPageHandlerFactory;
use Mezzio\Middleware\ErrorResponseGenerator;

class ConfigProvider
{
    public function __invoke(): array
    {
        if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'development') {
            return [
                'dev' => $this->getDevSettings(),
                ConfigAggregator::ENABLE_CACHE => false,
                'temp_config' => [
                     'disable_privileges' => false,
                ],
                'migrations'   => $this->getMigrations(),
                'sites' => $this->getSitesSettings(),
                'email' => [
                    'dsn' => 'smtp://mailpit:1025',
                    'site' => 'test@gemstracker.test',
                ],
                'password' => null,
                'dependencies' => [
                    'factories' => [
                        ErrorResponseGenerator::class => WhoopsErrorResponseGeneratorFactory::class,
                        'Mezzio\Whoops'               => WhoopsFactory::class,
                        'Mezzio\WhoopsPageHandler'    => WhoopsPageHandlerFactory::class,

                        Clockwork::class => ClockworkFactory::class,
                    ],
                    'aliases' => [
                        SmsClientInterface::class => DevMailSmsClient::class,
                    ],
                ],
                'pipeline' => [
                    DebugDumperMiddleware::class,
                    ClockworkMiddleware::class,
                ],
                'routes' => [
                    [
                        'name' => 'clockwork.api',
                        'path' => '/__clockwork/{id:latest|[0-9-]+}[/{direction:previous|next}[/{count:[\d+]}]]',
                        'allowed_methods' => ['GET'],
                        'middleware' => [
                            SecurityHeadersMiddleware::class,
                            ClockworkApiHandler::class,
                        ],
                    ],
                ],
                'whoops' => [
                    'json_exceptions' => [
                        'display'    => true,
                        'show_trace' => true,
                        'ajax_only'  => true,
                    ],
                ],
            ];
        }

        return [];
    }

    /**
     * @return mixed[]
     */
    protected function getDevSettings(): array
    {
        return [
            'currentUsername' => 'jjansen',
            'currentOrganizationId' => 70,
        ];
    }

    /**
     * @return mixed[]
     */
    protected function getMigrations(): array
    {
        return [
            /*'migrations' => [
                __DIR__ . '/configs/db/migrations',
            ],*/
            'seeds' => [
                __DIR__ . '/configs/db/seeds',
            ],
        ];
    }

    protected function getSitesSettings(): array
    {
        return [
            'allowed' => [
                [
                    'url' => 'http://gemstracker.test',
                ],
                [
                    'url' => 'http://localhost',
                ],
            ]
        ];
    }
}