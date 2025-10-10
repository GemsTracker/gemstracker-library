<?php

declare(strict_types=1);

namespace Gems\Communication;

use Gems\Event\CommFieldGatherEvent;
use Gems\Fake\Respondent;
use Gems\Fake\Token;
use Gems\Fake\User;
use Gems\Repository\RespondentRepository;
use Gems\Tracker;
use Gems\User\UserLoader;
use Psr\EventDispatcher\EventDispatcherInterface;
use Zalt\Loader\ProjectOverloader;

class CommFieldsRepository
{
    public function __construct(
        protected readonly CommunicationRepository $communicationRepository,
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly RespondentRepository $respondentRepository,
        protected readonly Tracker $tracker,
        protected readonly ProjectOverloader $overloader,
        protected readonly UserLoader $userLoader,
    )
    {
    }

    public function getCommFields(string $target, string $locale, string|int|null $id, int $organizationId = null): array
    {
        return match ($target) {
            'token' => $this->getTokenFields($id, $locale),
            'respondent' => $this->getRespondentFields($id, $organizationId, $locale),
            'staff' => $this->getStaffFields($id, $organizationId, $locale, false),
            'staffPassword' => $this->getStaffFields($id, $organizationId, $locale, true),
            default => $this->getOtherMailFields($target, $locale, $id, $organizationId),
        };
    }

    protected function getOtherMailFields(string $target, string $language, string|int|null $id, int|null $organizationId): array|null
    {
        $event = new CommFieldGatherEvent(
            $target,
            $language,
            $id,
            $organizationId,
        );

        $this->eventDispatcher->dispatch($event);

        return $event->fields;
    }

    protected function getRespondentFields(string|null $respondentId, int|null $organizationId, string $language): array|null
    {
        if ($respondentId === null || $organizationId === null) {
            /**
             * @var Respondent $fakeRespondent
             */
            $fakeRespondent = $this->overloader->create(Respondent::class);
            return $this->communicationRepository->getRespondentMailFields($fakeRespondent, $language);
        }

        $respondent = $this->respondentRepository->getRespondent($respondentId, $organizationId);
        if ($respondent->exists) {
            return $this->communicationRepository->getRespondentMailFields($respondent, $language);
        }

        return null;
    }

    protected function getStaffFields(string|null $loginName , int|null $organizationId, string $language, bool $passwordFields = false): array|null
    {
        if ($loginName === null || $organizationId === null) {
            /**
             * @var User $fakeUser
             */
            $fakeUser = $this->overloader->create(User::class);
            if ($passwordFields) {
                return $this->communicationRepository->getUserPasswordMailFields($fakeUser, $language);
            }
            return $this->communicationRepository->getUserMailFields($fakeUser, $language);
        }

        $user = $this->userLoader->getUser($loginName, $organizationId);
        if ($user->isActive()) {
            if ($passwordFields) {
                return $this->communicationRepository->getUserPasswordMailFields($user, $language);
            }
            return $this->communicationRepository->getUserMailFields($user, $language);
        }

        return null;
    }

    protected function getTokenFields(string|null $tokenId, string $language): array|null
    {
        if ($tokenId === null) {
            /**
             * @var Token $fakeToken
             */
            $fakeToken = $this->overloader->create(Token::class);
            return $this->communicationRepository->getTokenMailFields($fakeToken, $language);
        }

        $token = $this->tracker->getToken($tokenId);
        if ($token->exists) {
            return $this->communicationRepository->getTokenMailFields($token, $language);
        }

        return null;
    }
}