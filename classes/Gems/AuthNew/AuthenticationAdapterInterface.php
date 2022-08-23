<?php

namespace Gems\AuthNew;

interface AuthenticationAdapterInterface
{
    public function authenticate(): AuthenticationResult;
}
