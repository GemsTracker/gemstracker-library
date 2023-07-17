<?php

namespace Gems\Messenger\Handler;

use Gems\Messenger\Message\TokenResponse;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class AddTokenResponseToDataTableHandler
{
    public function __invoke(TokenResponse $message): void
    {

    }
}