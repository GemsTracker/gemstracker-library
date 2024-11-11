<?php

declare(strict_types=1);

namespace Gems\Messenger;

use Gems\Legacy\CurrentUserRepository;
use Gems\Messenger\Message\CurrentUserMessage;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;

class CurrentUserMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly CurrentUserRepository $currentUserRepository,
    )
    {}

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();
        if ($message instanceof CurrentUserMessage && $this->currentUserRepository->getCurrentLoginName() === null) {
            $this->currentUserRepository->setCurrentUserCredentials($message->getUserName(), $message->getOrganizationId());
            $this->currentUserRepository->getCurrentUser();
        }

        return $stack->next()->handle($envelope, $stack);
    }
}