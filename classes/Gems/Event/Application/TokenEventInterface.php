<?php

namespace Gems\Event\Application;

use Gems\Tracker\Token;

interface TokenEventInterface
{
    public function getToken(): Token;
}