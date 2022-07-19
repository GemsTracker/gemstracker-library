<?php

namespace Gems\Config;

class App
{
    public function __invoke(): array
    {
        return [
            'name' => 'GemsTracker',
            'description' => 'GEneric Medical Survey Tracker',
            'env' => isset($_ENV['APP_ENV']) ? $_ENV['APP_ENV'] : null,
            'key' => isset($_ENV['APP_KEY']) ? $_ENV['APP_KEY'] : null,
        ];
    }
}