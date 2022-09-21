<?php

namespace Gems\AuthNew\Adapter;

use Gems\AuthNew\GenericFailedAuthenticationResult;
use Gems\User\UserLoader;
use Laminas\Db\Adapter\Adapter;
use Symfony\Contracts\Translation\TranslatorInterface;

class GenericRoutedAuthentication implements AuthenticationAdapterInterface
{
    public function __construct(
        private readonly UserLoader $userLoader,
        private readonly TranslatorInterface $translator,
        private readonly Adapter $db,
        private readonly int $organizationId,
        private readonly string $username,
        private readonly string $password,
        private readonly string $ipAddress
    ) {
    }

    public function authenticate(): AuthenticationResult
    {
        $user = $this->userLoader->getUser(
            $this->username,
            $this->organizationId,
        );

        if ($user === null || $user->getUserDefinitionClass() === UserLoader::USER_NOLOGIN) { // TODO: Remove NOLOGIN
            return new GenericFailedAuthenticationResult(AuthenticationResult::FAILURE);
        }

        if (!$user->isActive()) {
            return new GenericFailedAuthenticationResult(AuthenticationResult::FAILURE);
        }

        if (!$user->isAllowedIpForLogin($this->ipAddress)) {
            return new GenericFailedAuthenticationResult(AuthenticationResult::DISALLOWED_IP, [
                $this->translator->trans('You are not allowed to login from this location.'),
            ]);
        }

        $adapter = match($user->getUserDefinitionClass()) {
            UserLoader::USER_STAFF => GemsTrackerAuthentication::fromUser($this->db, $user, $this->password),
        };

        return $adapter->authenticate();
    }
}
