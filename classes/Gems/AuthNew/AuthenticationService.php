<?php

namespace Gems\AuthNew;

use Gems\AuthNew\Adapter\AuthenticationAdapterInterface;
use Gems\AuthNew\Adapter\AuthenticationIdentityInterface;
use Gems\AuthNew\Adapter\AuthenticationIdentityType;
use Gems\AuthNew\Adapter\AuthenticationResult;
use Gems\AuthNew\Adapter\GemsTrackerAuthentication;
use Gems\Event\Application\AuthenticatedEvent;
use Gems\User\User;
use Gems\User\UserLoader;
use Laminas\Db\Adapter\Adapter;
use Mezzio\Session\SessionInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Contracts\Translation\TranslatorInterface;

class AuthenticationService
{
    public function __construct(
        private readonly SessionInterface $session,
        private readonly UserLoader $userLoader,
        private readonly EventDispatcher $eventDispatcher,
        private readonly Adapter $db,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function routedAuthenticate(
        int $organizationId,
        string $username,
        string $password,
        string $ipAddress
    ): AuthenticationResult {
        $user = $this->userLoader->getUser(
            $username,
            $organizationId,
        );

        if ($user === null || $user->getUserDefinitionClass() === UserLoader::USER_NOLOGIN) { // TODO: Remove NOLOGIN
            return new GenericFailedAuthenticationResult(AuthenticationResult::FAILURE);
        }

        if (!$user->isActive()) {
            return new GenericFailedAuthenticationResult(AuthenticationResult::FAILURE);
        }

        if (!$user->isAllowedIpForLogin($ipAddress)) {
            return new GenericFailedAuthenticationResult(AuthenticationResult::DISALLOWED_IP, [
                $this->translator->trans('You are not allowed to login from this location.'),
            ]);
        }

        $adapter = match($user->getUserDefinitionClass()) {
            UserLoader::USER_STAFF => GemsTrackerAuthentication::fromUser($this->db, $user, $password),
        };

        return $this->authenticate($adapter);
    }

    public function authenticate(AuthenticationAdapterInterface $adapter): AuthenticationResult
    {
        $result = $adapter->authenticate();

        if ($result->isValid()) {
            $identity = $result->getIdentity();

            $this->session->regenerate();
            $this->session->set('auth_data', [
                'auth_type' => $identity::class,
                'auth_params' => $identity->toArray(),
            ]);

            $event = new AuthenticatedEvent($result); // TODO: Not used yet
            $this->eventDispatcher->dispatch($event);
        } else {
            $this->session->regenerate();
            $this->session->set('auth_data', null);
        }

        return $result;
    }

    public function getIdentity(): ?AuthenticationIdentityInterface
    {
        $authData = $this->session->get('auth_data');
        if ($authData === null) {
            return null;
        }

        $type = AuthenticationIdentityType::from($authData['auth_type']);
        /** @var class-string<AuthenticationIdentityInterface> $class */
        $class = $type->value;
        return $class::fromArray($authData['auth_params']);
    }

    public function isLoggedIn(): bool
    {
        return $this->getIdentity() !== null;
    }

    public function getLoggedInUser(): ?User
    {
        $identity = $this->getIdentity();

        if ($identity === null) {
            return null; //new NotLoggedInUser();
        }

        return $this->userLoader->getUser(
            $identity->getLoginName(),
            $identity->getOrganizationId(),
        );
    }

    public function logout(): void
    {
        $this->session->unset('auth_data');
        $this->session->regenerate();
    }
}
