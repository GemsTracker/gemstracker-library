<?php

namespace Gems\AuthNew\Adapter;

use Gems\User\User;

class EmbedAuthenticationResult extends AuthenticationResult
{
    public function __construct(
        int $code,
        ?EmbedIdentity $identity,
        array $messages = [],
        public readonly ?User $systemUser = null,
        public readonly ?User $deferredUser = null,
    ) {
        parent::__construct(
            $code,
            $identity,
            $messages,
        );
    }
}
