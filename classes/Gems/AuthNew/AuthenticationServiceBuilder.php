<?php

namespace Gems\AuthNew;

use Gems\User\UserLoader;
use Laminas\Db\Adapter\Adapter;
use Mezzio\Session\SessionInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\Translation\TranslatorInterface;

class AuthenticationServiceBuilder
{
    public function __construct(
        private readonly UserLoader $userLoader,
        private readonly EventDispatcher $eventDispatcher,
        private readonly Adapter $db,
        private readonly TranslatorInterface $translator,
        private readonly array $config,
    ) {
    }

    public function buildAuthenticationService(SessionInterface $session): AuthenticationService
    {
        return new AuthenticationService(
            $session,
            $this->userLoader,
            $this->eventDispatcher,
            $this->db,
            $this->translator,
            $this->config,
        );
    }
}
