<?php

namespace Gems\AuthTfa;

use Gems\AuthNew\Adapter\EmbedIdentity;
use Gems\AuthNew\AuthenticationService;
use Gems\AuthTfa\Method\OtpMethodInterface;
use Gems\Middleware\ClientIpMiddleware;
use Gems\User\User;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ServerRequestInterface;

class TfaService
{
    public function __construct(
        private readonly SessionInterface $session,
        private readonly AuthenticationService $authenticationService,
        private readonly OtpMethodBuilder $otpMethodBuilder,
    ) {
    }

    public function getOtpMethod(): OtpMethodInterface
    {
        if (!$this->authenticationService->isLoggedIn()) {
            throw new \Exception('Not logged in');
        }

        $user = $this->authenticationService->getLoggedInUser();

        return $this->otpMethodBuilder->buildOtpMethod($user);
    }

    public function verify(string $code): bool
    {
        $tfaLoggedInValue = null;

        if ($this->authenticationService->isLoggedIn()) {
            $user = $this->authenticationService->getLoggedInUser();

            $otpMethod = $this->otpMethodBuilder->buildOtpMethod($user);

            if($otpMethod->verify($user, $code)) {
                $tfaLoggedInValue = $user->getUserId();
            }
        }

        $this->session->set('tfa_logged_in', $tfaLoggedInValue);

        return $tfaLoggedInValue !== null;
    }

    public function isLoggedIn(User $user): bool
    {
        return $this->session->get('tfa_logged_in') === $user->getUserId();
    }

    public function requiresAuthentication(User $user, ServerRequestInterface $request): bool
    {
        $identity = $this->authenticationService->getIdentity();
        if ($identity instanceof EmbedIdentity) {
            return false;
        }

        // todo: check if organization has TFA enabled at all?

        if (!$user->isTwoFactorRequired($request->getAttribute(ClientIpMiddleware::CLIENT_IP_ATTRIBUTE))) {
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
