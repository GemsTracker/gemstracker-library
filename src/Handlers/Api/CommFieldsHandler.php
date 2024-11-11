<?php

namespace Gems\Handlers\Api;

use Gems\Communication\CommunicationRepository;
use Gems\Event\CommFieldGatherEvent;
use Gems\Fake\Respondent;
use Gems\Fake\Token;
use Gems\Fake\User;
use Gems\Locale\Locale;
use Gems\Middleware\LocaleMiddleware;
use Gems\Repository\CommFieldRepository;
use Gems\Repository\MailRepository;
use Gems\Repository\RespondentRepository;
use Gems\Tracker;
use Gems\User\UserLoader;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zalt\Loader\ProjectOverloader;
use Zalt\Model\MetaModelInterface;

class CommFieldsHandler implements RequestHandlerInterface
{
    public function __construct(
        protected readonly CommunicationRepository $communicationRepository,
        protected readonly RespondentRepository $respondentRepository,
        protected readonly UserLoader $userLoader,
        protected readonly CommFieldRepository $commFieldRepository,
        protected readonly Tracker $tracker,
        protected readonly array $config,
        protected readonly ProjectOverloader $overloader,
        protected readonly EventDispatcherInterface $eventDispatcher,
    )
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $target = $request->getAttribute('target');
        if ($target === null) {
            return new JsonResponse(['error' => 'Target required']);
        }

        $targets = $this->commFieldRepository->getCommFieldTypes();
        if (!isset($targets[$target])) {
            return new JsonResponse(['error' => 'No valid target']);
        }

        /**
         * @var Locale $locale
         */
        $locale = $request->getAttribute(LocaleMiddleware::LOCALE_ATTRIBUTE);
        $queryParams = $request->getQueryParams();
        $id = $request->getAttribute(MetaModelInterface::REQUEST_ID) ?? $queryParams['id'] ?? null;
        $organizationId = $request->getAttribute('organizationId') ?? $queryParams['organizationId'] ?? null;

        $commFields = match ($target) {
            'token' => $this->getTokenFields($id, $locale->getLanguage()),
            'respondent' => $this->getRespondentFields($id, $organizationId, $locale->getLanguage()),
            'staff' => $this->getStaffFields($id, $organizationId, $locale->getLanguage(), false),
            'staffPassword' => $this->getStaffFields($id, $organizationId, $locale->getLanguage(), true),
            default => $this->getOtherMailFields($target, $locale->getLanguage(), $id, $organizationId),
        };

        if ($commFields) {
            return new JsonResponse($commFields);
        }
        return new EmptyResponse();
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
