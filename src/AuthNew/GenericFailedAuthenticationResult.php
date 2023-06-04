<?php

namespace Gems\AuthNew;

use Gems\AuthNew\Adapter\AuthenticationResult;

class GenericFailedAuthenticationResult extends AuthenticationResult
{
    public function __construct(int $code, array $messages = []) {
        parent::__construct($code, null, $messages);
    }
}
