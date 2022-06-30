<?php

namespace Gems\Config;

class App
{
    public function __invoke(): array
    {
        return [
            'name' => 'GemsTracker',
            'description' => 'GEneric Medical Survey Tracker',
        ];
    }
}