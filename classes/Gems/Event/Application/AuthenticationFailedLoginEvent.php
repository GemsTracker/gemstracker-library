<?php

namespace Gems\Event\Application;

use Gems\AuthNew\Adapter\AuthenticationResult;
use Symfony\Contracts\EventDispatcher\Event;

class AuthenticationFailedLoginEvent extends Event
{
    public const NAME = 'auth.failed-login';

    public function __construct(public readonly AuthenticationResult $result)
    {
    }
}
