<?php

namespace Gems\AuthNew;

use Gems\User\User;

class GemsTrackerAuthenticationResult extends AuthenticationResult
{
    public function __construct(
        int $code,
        ?AuthenticationIdentityInterface $identity,
        array $messages = [],
        public readonly ?User $user = null,
    ) {
        parent::__construct(
            $code,
            $identity,
            $messages,
        );
    }
}
