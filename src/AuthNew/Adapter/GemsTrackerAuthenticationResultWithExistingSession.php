<?php

namespace Gems\AuthNew\Adapter;

use Gems\User\User;

class GemsTrackerAuthenticationResultWithExistingSession extends GemsTrackerAuthenticationResult implements HasSessionKeyInterface
{
    public function __construct(
        int $code,
        ?GemsTrackerIdentity $identity,
        array $messages = [],
        ?User $user = null,
    )
    {
        parent::__construct($code, $identity, $messages, $user);
    }

    public function getSessionKey(?string $default = null): ?string
    {
        if ($this->getCode() === AuthenticationResult::SUCCESS && $this->user) {
            return $this->user->getSessionKey() ?? $default;
        }

        return $default;
    }
}