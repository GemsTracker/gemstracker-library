<?php

namespace Gems\Config;

use Gems\Helper\Env;

class App
{
    public function __invoke(): array
    {
        return [
            'name' => 'GemsTracker',
            'description' => 'GEneric Medical Survey Tracker',
            'env' => Env::get('APP_ENV', 'production'),
            'key' => Env::get('APP_KEY'),
        ];
    }
}