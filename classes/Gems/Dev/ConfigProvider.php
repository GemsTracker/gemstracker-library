<?php

namespace Gems\Dev;

class ConfigProvider
{
    public function __invoke()
    {
        return [
            'dev' => $this->getDevSettings(),
            'migrations'   => $this->getMigrations(),
        ];
    }

    /**
     * @return mixed[]
     */
    protected function getDevSettings(): array
    {
        return [
            'currentUsername' => 'jjansen',
            'currentOrganizationId' => '70',
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
}