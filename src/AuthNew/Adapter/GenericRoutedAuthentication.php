<?php

namespace Gems\AuthNew\Adapter;

use Gems\AuthNew\GenericFailedAuthenticationResult;
use Gems\Exception;
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
        $user = $this->userLoader->getUserOrNull($this->username, $this->organizationId);

        if ($user === null) {
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

        try {
            $adapter = match($user->getUserDefinitionClass()) {
                UserLoader::USER_STAFF => GemsTrackerAuthentication::fromUser($this->db, $user, $this->password),
            };
        } catch (\UnhandledMatchError $e) {
            throw new Exception('Unsupported user definition class');
        }

        return $adapter->authenticate();
    }
}
