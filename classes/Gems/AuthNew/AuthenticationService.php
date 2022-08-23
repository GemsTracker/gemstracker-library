<?php

namespace Gems\AuthNew;

use Gems\User\User;
use Gems\User\UserLoader;
use Laminas\Db\Adapter\Adapter;
use Mezzio\Session\SessionInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class AuthenticationService
{

    public function __construct(
        private readonly SessionInterface $session,
        private readonly UserLoader $userLoader,
        private readonly EventDispatcher $eventDispatcher,
        private readonly Adapter $db,
    ) {
    }

    public function routedAuthenticate(int $organizationId, string $username, string $password): AuthenticationResult
    {
        $user = $this->userLoader->getUser(
            $username,
            $organizationId,
        );

        $adapter = match($user->getUserDefinitionClass()) {
            UserLoader::USER_STAFF => GemsTrackerAuthentication::fromUser($this->db, $user, $password),
        };

        return $this->authenticate($adapter);
    }

    public function authenticate(AuthenticationAdapterInterface $adapter): AuthenticationResult
    {
        $result = $adapter->authenticate();

        if ($result->isValid()) {
            $user = $result->getUser();

            $this->session->set('auth_data', [
                'auth_type' => $result->getAuthenticationType()->value,
                'login_name' => $user->getLoginName(),
                'organization_id' => $user->getCurrentOrganizationId(),
            ]);

            $event = new AuthenticatedEvent($result);
            $this->eventDispatcher->dispatch($event);
        } else {
            $this->session->set('auth_data', null);
        }

        return $result;
    }

    public function isLoggedIn(): bool
    {
        $auth_data = $this->session->get('auth_data');

        return $auth_data !== null && !empty($auth_data['login_name']);
    }

    public function getLoggedInUser(): ?User
    {
        $auth_data = $this->session->get('auth_data');

        if ($auth_data === null) {
            return null; //new NotLoggedInUser();
        }

        return $this->userLoader->getUser(
            $auth_data['login_name'],
            $auth_data['organization_id'],
        );
    }

    public function getAuthenticationType(): ?AuthenticationAdapterType
    {
        $auth_data = $this->session->get('auth_data');

        if ($auth_data === null) {
            return null;
        }

        return AuthenticationAdapterType::from($auth_data['auth_type']);
    }

    public function logout(): void
    {
        $this->session->unset('auth_data');
    }
}
