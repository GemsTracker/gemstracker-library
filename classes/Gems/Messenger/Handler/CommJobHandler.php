<?php

namespace Gems\Messenger\Handler;

use Gems\Messenger\Message\CommJob;
use Gems\Messenger\Message\SendCommJobMessage;
use Gems\Repository\CommJobRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class CommJobHandler
{
    public function __construct(
        protected CommJobRepository $commJobRepository,
        protected MessageBusInterface $messageBus,
    )
    {}

    public function __invoke(CommJob $commJob)
    {
        $sendableTokens = $this->commJobRepository->getSendableTokens($commJob->getId());

        foreach($sendableTokens['send'] as $sendableTokenId) {
            if (!$this->commJobRepository->isTokenInQueue($sendableTokenId)) {
                $message = new SendCommJobMessage($commJob->getId(), $sendableTokenId);
                $this->commJobRepository->setTokenIsInQueue($sendableTokenId);
                $this->messageBus->dispatch($message);
            }

        }

        foreach($sendableTokens['markSent'] as $sendableTokenId) {

            if (!$this->commJobRepository->isTokenInQueue($sendableTokenId)) {
                $message = new SendCommJobMessage($commJob->getId(), $sendableTokenId);
                $this->commJobRepository->setTokenIsInQueue($sendableTokenId);
                $this->messageBus->dispatch($message);
            }
        }
    }
}