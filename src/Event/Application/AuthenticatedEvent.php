<?php

namespace Gems\Event\Application;

use Gems\AuthNew\Adapter\AuthenticationResult;
use Symfony\Contracts\EventDispatcher\Event;

class AuthenticatedEvent extends Event
{
    public const NAME = 'auth.authenticated';

    public function __construct(public readonly AuthenticationResult $result)
    {
    }
}
