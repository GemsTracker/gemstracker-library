<?php

namespace Gems\AuthNew\Adapter;

use Gems\User\User;

class GemsTrackerAuthenticationResult extends AuthenticationResult
{
    public function __construct(
        int $code,
        ?GemsTrackerIdentity $identity,
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
