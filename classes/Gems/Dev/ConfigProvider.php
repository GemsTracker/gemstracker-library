<?php

namespace Gems\Dev;

class ConfigProvider
{
    public function __invoke(): array
    {
        if (getenv('APP_ENV') === 'development') {
            return [
                'dev' => $this->getDevSettings(),
                'migrations'   => $this->getMigrations(),
                'sites' => $this->getSitesSettings(),
                'mail' => [
                    'dsn' => 'smtp://mailhog:1025',
                ]
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