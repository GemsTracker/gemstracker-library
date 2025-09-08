<?php

namespace Gems\AuthNew\Adapter;

use Gems\Repository\RespondentRepository;
use Gems\User\Embed\EmbeddedAuthAbstract;
use Gems\User\Embed\EmbeddedUserData;
use Gems\User\Embed\UpdatingAuthInterface;
use Gems\User\User;
use Gems\User\UserLoader;

class EmbedAuthentication implements AuthenticationAdapterInterface
{
    public function __construct(
        private readonly UserLoader $userLoader,
        private readonly string $systemUserLoginName,
        private readonly string $systemUserSecretKey,
        private string $deferredLoginName,
        private string $patientId,
        private readonly int $organizationId,
        private readonly string $ipAddress,
    ) {
    }

    private function makeFailResult(int $code, array $messages = []): AuthenticationResult
    {
        return new EmbedAuthenticationResult($code, null, $messages);
    }

    public function authenticate(): AuthenticationResult
    {
        $systemUser = $this->userLoader->getUserOrNull($this->systemUserLoginName, $this->organizationId);
        if ($systemUser === null || !$systemUser->isActive()) {
            return $this->makeFailResult(AuthenticationResult::FAILURE, ['Nonexistent or inactive']);
        }

        $systemUserData = $this->userLoader->getEmbedderData($systemUser);
        if (! $systemUserData instanceof EmbeddedUserData) {
            return $this->makeFailResult(AuthenticationResult::FAILURE, ['No user data']);
        }

        if (!$systemUserData->isAllowedIpForLogin($this->ipAddress)) {
            return $this->makeFailResult(AuthenticationResult::DISALLOWED_IP, ['You are not allowed to login from this location.']);
        }

        $authClass = $systemUserData->getAuthenticator();
        if (!$authClass instanceof EmbeddedAuthAbstract) {
            return $this->makeFailResult(AuthenticationResult::FAILURE, ['No authenticator']);
        }

        $authClass->setDeferredLogin($this->deferredLoginName);
        $authClass->setPatientNumber($this->patientId);
        $authClass->setOrganizations([$this->organizationId]);
        $result = $authClass->authenticate($systemUser, $systemUserData, $this->systemUserSecretKey);

        if (!$result) {
            return $this->makeFailResult(AuthenticationResult::FAILURE, ['Invalid credentials']);
        }

        // The patient Id and deferred login name have been extracted from the encrypted key.
        if ($authClass instanceof UpdatingAuthInterface) {
            $this->patientId = $authClass->getPatientNumber();
            $this->deferredLoginName = $authClass->getDeferredLogin();
        }

        $deferredUser = $systemUserData->getDeferredUser($systemUser, $this->deferredLoginName);
        if (!$deferredUser instanceof User || !$deferredUser->isActive()) {
            return $this->makeFailResult(AuthenticationResult::FAILURE_DEFERRED, ['No deferred user']);
        }

        /*$respondent = $this->respondentRepository->getPatient($this->patientId, $systemUser->getCurrentOrganizationId());

        if (!is_array($respondent)) {
            return $this->makeFailResult(AuthenticationResult::FAILURE_DEFERRED, ['Nonexistent patient']);
        }*/

        $identity = new EmbedIdentity(
            $systemUser->getLoginName(),
            $deferredUser->getLoginName(),
            $this->patientId,
            $deferredUser->getBaseOrganizationId(),
        );

        return new EmbedAuthenticationResult(AuthenticationResult::SUCCESS, $identity, [], $systemUser, $deferredUser);
    }
}
