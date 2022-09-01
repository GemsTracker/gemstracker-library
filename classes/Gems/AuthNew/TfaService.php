<?php

namespace Gems\AuthNew;

use Gems\User\User;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ServerRequestInterface;

class TfaService
{
    public function __construct(
        private readonly SessionInterface $session,
        private readonly AuthenticationService $authenticationService,
        private readonly ServerRequestInterface $request,
    ) {
    }

    public function authenticate(TfaAdapterInterface $adapter): TfaResult
    {
        $result = $adapter->authenticate();

        if ($result->isValid()) {
            $user = $result->getUser();

            $this->session->set('tfa_logged_in', $user->getUserId());
        } else {
            $this->session->set('tfa_logged_in', null);
        }

        return $result;
    }

    public function isLoggedIn(User $user): bool
    {
        return $this->session->get('tfa_logged_in') === $user->getUserId();
    }

    public function requiresAuthentication(User $user): bool
    {
        $identity = $this->authenticationService->getIdentity();
        if ($identity instanceof EmbedIdentity) {
            return false;
        }

        // todo: check if organization has TFA enabled at all?

        if (!$user->isTwoFactorRequired($this->request->getServerParams()['REMOTE_ADDR'])) {
            return false;
        }

        if ($this->isLoggedIn($user)) {
            return false;
        }

        return true;
    }

    public function logout(): void
    {
        $this->session->unset('tfa_logged_in');
    }
}
