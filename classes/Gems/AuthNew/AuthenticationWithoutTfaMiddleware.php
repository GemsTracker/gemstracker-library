<?php

namespace Gems\AuthNew;

class AuthenticationWithoutTfaMiddleware extends AuthenticationMiddleware
{
    protected const CHECK_TFA = false;
}
