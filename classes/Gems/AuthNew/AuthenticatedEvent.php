<?php

namespace Gems\AuthNew;

use Symfony\Contracts\EventDispatcher\Event;

class AuthenticatedEvent extends Event
{
    public const NAME = 'auth.authenticated';

    public function __construct(public readonly AuthenticationResult $result)
    {
    }
}
