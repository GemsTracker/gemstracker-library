<?php

namespace Gems\AuthNew\Adapter;

interface AuthenticationAdapterInterface
{
    public function authenticate(): AuthenticationResult;
}
