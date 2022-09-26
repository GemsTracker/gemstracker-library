<?php

namespace Gems\AuthNew\Adapter;

use Gems\Repository\RespondentRepository;
use Gems\User\Embed\EmbeddedAuthAbstract;
use Gems\User\Embed\EmbeddedUserData;
use Gems\User\User;
use Gems\User\UserLoader;

class EmbedAuthentication implements AuthenticationAdapterInterface
{
    public function __construct(
        private readonly UserLoader $userLoader,
        private readonly RespondentRepository $respondentRepository,
        private readonly string $systemUserLoginName,
        private readonly string $systemUserSecretKey,
        private readonly string $deferredLoginName,
        private readonly string $patientId,
        private readonly int $organizationId,
    ) {
    }

    private function makeFailResult(int $code, array $messages = []): AuthenticationResult
    {
        return new EmbedAuthenticationResult($code, null, $messages);
    }

    public function authenticate(): AuthenticationResult
    {
        $systemUser = $this->userLoader->getUser($this->systemUserLoginName, $this->organizationId);
        if ($systemUser === null || !$systemUser->isActive()) {
            return $this->makeFailResult(AuthenticationResult::FAILURE, ['Nonexistent or inactive']);
        }

        $systemUserData = $systemUser->getEmbedderData();
        if (! $systemUserData instanceof EmbeddedUserData) {
            return $this->makeFailResult(AuthenticationResult::FAILURE, ['No user data']);
        }

        $authClass = $systemUserData->getAuthenticator();
        if (!$authClass instanceof EmbeddedAuthAbstract) {
            return $this->makeFailResult(AuthenticationResult::FAILURE, ['No authenticator']);
        }

        $authClass->setDeferredLogin($this->deferredLoginName);
        $authClass->setPatientNumber($this->patientId);
        $authClass->setOrganizations([$this->organizationId]);
        $result = $authClass->authenticate($systemUser, $this->systemUserSecretKey);

        if (!$result) {
            return $this->makeFailResult(AuthenticationResult::FAILURE, ['Invalid credentials']);
        }

        $deferredUser = $systemUserData->getDeferredUser($systemUser, $this->deferredLoginName);
        if (!$deferredUser instanceof User || !$deferredUser->isActive()) {
            return $this->makeFailResult(AuthenticationResult::FAILURE_DEFERRED, ['No deferred user']);
        }

        $respondent = $this->respondentRepository->getPatient($this->patientId, $systemUser->getCurrentOrganizationId());

        if ($respondent === false) {
            return $this->makeFailResult(AuthenticationResult::FAILURE_DEFERRED, ['Nonexistent patient']);
        }

        $identity = new EmbedIdentity(
            $systemUser->getLoginName(),
            $deferredUser->getLoginName(),
            $respondent['gr2o_patient_nr'],
            $systemUser->getCurrentOrganizationId(),
        );

        return new EmbedAuthenticationResult(AuthenticationResult::SUCCESS, $identity, [], $systemUser, $deferredUser);
    }
}
