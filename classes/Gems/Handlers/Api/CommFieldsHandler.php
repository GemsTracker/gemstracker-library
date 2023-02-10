<?php

namespace Gems\Handlers\Api;

use Gems\Communication\CommunicationRepository;
use Gems\Fake\Respondent;
use Gems\Fake\Token;
use Gems\Fake\User;
use Gems\Middleware\LocaleMiddleware;
use Gems\Repository\MailRepository;
use Gems\Repository\RespondentRepository;
use Gems\Tracker;
use Gems\User\UserLoader;
use Laminas\Diactoros\Response\EmptyResponse;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zalt\Loader\ConstructorProjectOverloader;

class CommFieldsHandler implements RequestHandlerInterface
{
    public function __construct(
        protected CommunicationRepository $communicationRepository,
        protected RespondentRepository $respondentRepository,
        protected UserLoader $userLoader,
        protected MailRepository $mailRepository,
        protected Tracker $tracker,
        protected array $config,
        protected ConstructorProjectOverloader $overloader
    )
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $target = $request->getAttribute('target');
        if ($target === null) {
            return new JsonResponse(['error' => 'Target required']);
        }

        $targets = $this->mailRepository->getMailTargets();
        if (!isset($targets[$target])) {
            return new JsonResponse(['error' => 'No valid target']);
        }

        return match ($target) {
            'token' => $this->getTokenFields($request),
            'respondent' => $this->getRespondentFields($request),
            'staff' => $this->getStaffFields($request),
            'staffPassword' => $this->getStaffFields($request, true),
            default => new EmptyResponse(),
        };
    }

    protected function getRespondentFields(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');
        if ($id === null && isset($queryParams['id'])) {
            $id = $queryParams['id'];
        }
        $organizationId = $request->getAttribute('organizationId');
        if ($organizationId === null && isset($queryParams['organizationId'])) {
            $organizationId = $queryParams['organizationId'];
        }

        $locale = $request->getAttribute(LocaleMiddleware::LOCALE_ATTRIBUTE);
        $language = $locale->getLanguage();
        if ($id === null || $organizationId === null) {
            /**
             * @var $fakeRespondent Respondent
             */
            $fakeRespondent = $this->overloader->create(Respondent::class);
            $mailFields = $this->communicationRepository->getRespondentMailFields($fakeRespondent, $language);
            return new JsonResponse($mailFields);
        }

        $respondent = $this->respondentRepository->getRespondent($id, $organizationId);
        if ($respondent->exists) {
            $mailFields = $this->communicationRepository->getRespondentMailFields($respondent, $language);
            return new JsonResponse($mailFields);
        }

        return new EmptyResponse();
    }

    protected function getStaffFields(ServerRequestInterface $request, bool $passwordFields = false): ResponseInterface
    {
        $id = $request->getAttribute('id');
        if ($id === null && isset($queryParams['id'])) {
            $id = $queryParams['id'];
        }
        $organizationId = $request->getAttribute('organizationId');
        if ($organizationId === null && isset($queryParams['organizationId'])) {
            $organizationId = $queryParams['organizationId'];
        }

        $locale = $request->getAttribute(LocaleMiddleware::LOCALE_ATTRIBUTE);
        $language = $locale->getLanguage();
        if ($id === null || $organizationId === null) {
            /**
             * @var $fakeUser User
             */
            $fakeUser = $this->overloader->create(User::class);
            if ($passwordFields) {
                $mailFields = $this->communicationRepository->getUserPasswordMailFields($fakeUser, $language);
            } else {
                $mailFields = $this->communicationRepository->getUserMailFields($fakeUser, $language);
            }
            return new JsonResponse($mailFields);
        }

        $user = $this->userLoader->getUser($id, $organizationId);
        if ($user->isActive()) {
            if ($passwordFields) {
                $mailFields = $this->communicationRepository->getUserPasswordMailFields($user, $language);
            } else {
                $mailFields = $this->communicationRepository->getUserMailFields($user, $language);
            }
            return new JsonResponse($mailFields);
        }

        return new EmptyResponse();
    }

    protected function getTokenFields(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $id = $request->getAttribute('id');
        if ($id === null && isset($queryParams['id'])) {
            $id = $queryParams['id'];
        }
        $locale = $request->getAttribute(LocaleMiddleware::LOCALE_ATTRIBUTE);
        $language = $locale->getLanguage();
        if ($id === null) {
            $mailFields = $this->communicationRepository->getTokenMailFields(new Token($this->overloader), $language);
            return new JsonResponse($mailFields);
        }

        $token = $this->tracker->getToken($id);
        if ($token->exists) {
            $mailFields = $this->communicationRepository->getTokenMailFields($token, $language);
            return new JsonResponse($mailFields);
        }

        return new EmptyResponse();
    }
}
