<?php

namespace Gems\AuthNew;

use Gems\AuthNew\Adapter\AuthenticationAdapterInterface;
use Gems\AuthNew\Adapter\AuthenticationIdentityInterface;
use Gems\AuthNew\Adapter\AuthenticationIdentityType;
use Gems\AuthNew\Adapter\AuthenticationResult;
use Gems\Event\Application\AuthenticatedEvent;
use Gems\Event\Application\AuthenticationFailedLoginEvent;
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
                'auth_last_seen_at' => time(),
                'auth_session_key' => $sessionKey,
            ]);

            $user = $this->getLoggedInUser();
            $user->setSessionKey($sessionKey);

            $event = new AuthenticatedEvent($result); // TODO: Not used yet
            $this->eventDispatcher->dispatch($event, $event::NAME);
        } else {
            $this->session->regenerate();
            $this->session->set('auth_data', null);

            $event = new AuthenticationFailedLoginEvent($result); // TODO: Not used yet
            $this->eventDispatcher->dispatch($event, $event::NAME);
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

    public function checkValid(bool $activeRequest = true, ?User $user = null): bool
    {
        $authData = $this->session->get('auth_data');
        if ($user === null) {
            $user = $this->getLoggedInUser();
        } else {
            // User can be passed to skip an extra DB query, but we must check that this is actually
            // the right user
            $identity = $this->getIdentity();
            if ($identity === null || $identity->getLoginName() !== $user->getLoginName()) {
                $this->logout();
                return false;
            }
        }

        if ($authData === null || $user === null) {
            return false;
        }

        $validUntil = min(
            $authData['auth_login_at'] + $this->config['session']['max_total_time'],
            $authData['auth_last_seen_at'] + $this->config['session']['max_away_time'],
            $authData['auth_last_active_at'] + $this->config['session']['max_idle_time'],
        );

        if (time() > $validUntil) {
            $this->logout();
            return false;
        }

        if ($user->getSessionKey() !== $authData['auth_session_key']) {
            $this->logout();
            return false;
        }

        if ($activeRequest) {
            $authData['auth_last_active_at'] = time();
        }
        $authData['auth_last_seen_at'] = time();
        $this->session->set('auth_data', $authData);

        return true;
    }

    public function getIdleAllowedUntil(): ?int
    {
        $authData = $this->session->get('auth_data');

        if ($authData === null) {
            return null;
        }

        return $authData['auth_last_active_at'] + $this->config['session']['max_idle_time'];
    }
}
