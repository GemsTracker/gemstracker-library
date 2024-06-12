<?php

namespace Gems\AuthNew\Adapter;

use Gems\Api\Middleware\ApiAuthenticationMiddleware;
use Gems\Exception\AuthenticationException;
use Gems\User\UserAccessTokenGenerator;
use Gems\User\UserLoader;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Psr\Http\Message\ServerRequestInterface;

class AccessTokenAuthentication implements AuthenticationAdapterInterface
{
    public function __construct(
        private readonly ResourceServer $resourceServer,
        private readonly ServerRequestInterface $request,
        private readonly UserLoader $userLoader,
    )
    {}

    public function authenticate(): AuthenticationResult
    {
        try {
            $request = $this->resourceServer->validateAuthenticatedRequest($this->request);
        } catch(OAuthServerException $e) {
            return new GemsTrackerAuthenticationResult(AuthenticationResult::FAILURE, null, [$e->getMessage()]);
        }

        if ($request->getAttribute(ApiAuthenticationMiddleware::CURRENT_USER_NAME) === null) {
            return new GemsTrackerAuthenticationResult(AuthenticationResult::FAILURE, null, [
                'Authentication error, no user login found',
            ]);
        }
        if ($request->getAttribute(ApiAuthenticationMiddleware::CURRENT_USER_ORGANIZATION) === null) {
            return new GemsTrackerAuthenticationResult(AuthenticationResult::FAILURE, null, [
                'Authentication error, no user organization found',
            ]);
        }
        if ($request->getAttribute(UserAccessTokenGenerator::USER_SESSION_KEY_ATTRIBUTE) === null) {
            return new GemsTrackerAuthenticationResult(AuthenticationResult::FAILURE, null, [
                'Authentication error, no user organization found',
            ]);
        }



        $identity = new GemsTrackerIdentity(
            $request->getAttribute(ApiAuthenticationMiddleware::CURRENT_USER_NAME),
            $request->getAttribute(ApiAuthenticationMiddleware::CURRENT_USER_ORGANIZATION),
        );
        try {
            $user = $this->userLoader->getUserOrNull($identity->getLoginName(), $identity->getOrganizationId());
        } catch(AuthenticationException $e) {
            return new GemsTrackerAuthenticationResult(AuthenticationResult::FAILURE, null, [$e->getMessage()]);
        }

        $sessionKey = $request->getAttribute(UserAccessTokenGenerator::USER_SESSION_KEY_ATTRIBUTE);
        if ($sessionKey === null || $sessionKey !== $user->getSessionKey()) {
            return new GemsTrackerAuthenticationResult(AuthenticationResult::FAILURE, null, [
                'Authentication error, session no longer valid',
            ]);
        }

        return new GemsTrackerAuthenticationResultWithExistingSession(
            AuthenticationResult::SUCCESS,
            $identity,
            [],
            $user,
            $request->getAttribute('user_session_key'),
        );
    }
}