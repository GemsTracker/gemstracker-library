<?php

namespace Gems\AuthNew;

class GenericFailedAuthenticationResult extends AuthenticationResult
{
    public function __construct(int $code, array $messages = []) {
        parent::__construct($code, null, $messages);
    }
}
