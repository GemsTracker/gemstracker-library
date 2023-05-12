<?php

namespace Gems\AuthNew;

use Gems\User\UserLoader;
use Mezzio\Session\SessionInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class AuthenticationServiceBuilder
{
    public function __construct(
        private readonly UserLoader $userLoader,
        private readonly EventDispatcher $eventDispatcher,
        private readonly array $config,
    ) {
    }

    public function buildAuthenticationService(SessionInterface $session): AuthenticationService
    {
        return new AuthenticationService(
            $session,
            $this->userLoader,
            $this->eventDispatcher,
            $this->config,
        );
    }
}
