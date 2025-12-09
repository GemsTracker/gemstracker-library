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
        $this->setCurrentUser($envelope);

        return $stack->next()->handle($envelope, $stack);
    }

    private function setCurrentUser(Envelope $envelope): void
    {
        /** @var ?CurrentUserStamp $userStamp */
        $userStamp = $envelope->last(CurrentUserStamp::class);
        if ($userStamp) {
            if ($userStamp->userId) {
                $this->currentUserRepository->setCurrentUserId($userStamp->userId);
            }
            if ($userStamp->username && $userStamp->organizationId) {
                $this->currentUserRepository->setCurrentUserCredentials($userStamp->username, $userStamp->organizationId);
            }
            return;
        }

        $message = $envelope->getMessage();
        if ($message instanceof CurrentUserMessage && $this->currentUserRepository->getCurrentLoginName() === null) {
            $this->currentUserRepository->setCurrentUserCredentials($message->getUserName(), $message->getOrganizationId());
            $this->currentUserRepository->getCurrentUser();
        }
    }
}