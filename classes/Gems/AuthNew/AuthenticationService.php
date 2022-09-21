<?php

namespace Gems\AuthNew;

use Gems\AuthNew\Adapter\AuthenticationAdapterInterface;
use Gems\AuthNew\Adapter\AuthenticationIdentityInterface;
use Gems\AuthNew\Adapter\AuthenticationIdentityType;
use Gems\AuthNew\Adapter\AuthenticationResult;
use Gems\Event\Application\AuthenticatedEvent;
use Gems\User\User;
use Gems\User\UserLoader;
use Mezzio\Session\SessionInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class AuthenticationService
{
    public function __construct(
        private readonly SessionInterface $session,
        private readonly UserLoader $userLoader,
        private readonly EventDispatcher $eventDispatcher,
        private readonly array $config,
    ) {
    }

    public function authenticate(AuthenticationAdapterInterface $adapter): AuthenticationResult
    {
        $result = $adapter->authenticate();

        if ($result->isValid()) {
            $identity = $result->getIdentity();

            $sessionKey = bin2hex(random_bytes(16));

            $this->session->regenerate();
            $this->session->set('auth_data', [
                'auth_type' => $identity::class,
                'auth_params' => $identity->toArray(),
                'auth_login_at' => time(),
                'auth_last_active_at' => time(),
                'auth_session_key' => $sessionKey,
            ]);

            $user = $this->getLoggedInUser();
            $user->setSessionKey($sessionKey);

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
        $this->session->clear();
    }

    public function checkValid(): bool
    {
        $authData = $this->session->get('auth_data');
        $user = $this->getLoggedInUser();

        if ($authData === null || $user === null) {
            return false;
        }

        if (time() - $authData['auth_login_at'] > $this->config['session']['max_total_time']) {
            $this->logout();
            return false;
        }

        if (time() - $authData['auth_last_active_at'] > $this->config['session']['max_away_time']) {
            $this->logout();
            return false;
        }

        if ($user->getSessionKey() !== $authData['auth_session_key']) {
            $this->logout();
            return false;
        }

        $authData['auth_last_active_at'] = time();
        $this->session->set('auth_data', $authData);

        return true;
    }
}
