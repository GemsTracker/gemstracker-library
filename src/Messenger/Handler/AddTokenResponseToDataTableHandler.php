<?php

namespace Gems\Messenger\Handler;

use Gems\Messenger\Message\TokenResponse;
use Gems\Repository\ResponseDataRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class AddTokenResponseToDataTableHandler
{
    public function __construct(
        private readonly ResponseDataRepository $responseDataRepository,
    )
    {}
    public function __invoke(TokenResponse $message): void
    {
        $this->responseDataRepository->addResponses($message->getTokenId(), $message->getResponses(), $message->getUserId());
    }
}