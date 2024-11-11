<?php

namespace Gems\Handlers\Auth;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Gems\AuthNew\Adapter\AccessTokenAuthentication;
use Gems\AuthNew\AuthenticationServiceBuilder;
use Gems\Helper\Env;
use Gems\User\UserLoader;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use Laminas\Diactoros\ServerRequest;
use League\OAuth2\Server\ResourceServer;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AccessTokenSessionLoginHandler implements RequestHandlerInterface
{
    private string $payloadParamName = 'key';

    private string $defaultEntryRoute;

    public function __construct(
        private readonly ResourceServer $resourceServer,
        private readonly AuthenticationServiceBuilder $authenticationServiceBuilder,
        private readonly UserLoader $userLoader,
        private readonly UrlHelper $urlHelper,
        array $config,
    )
    {
        $this->defaultEntryRoute = $config['defaultEntryRoute'] ?? 'respondent.index';
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        if (!isset($queryParams[$this->payloadParamName])) {
            return new JsonResponse(
                [
                    'error' => 'authentication_failed',
                    'message' => 'No payload found',
                ],
                401
            );
        }
        $decryptedPayload = $this->decryptPayload($queryParams[$this->payloadParamName]);
        if ($decryptedPayload === null) {
            return new JsonResponse(
                [
                    'error' => 'authentication_failed',
                    'message' => 'Payload could not be decrypted',
                ],
                401
            );
        }

        $accessTokenRequest = new ServerRequest(
            method: 'GET',
            headers: [
                'authorization' => $decryptedPayload['access_token']
            ],
        );

        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);
        $authenticationService = $this->authenticationServiceBuilder->buildAuthenticationService($session);
        $authenticationResult = $authenticationService->authenticate(
            new AccessTokenAuthentication(
                $this->resourceServer,
                $accessTokenRequest,
                $this->userLoader,
            )
        );

        if ($authenticationResult->isValid()) {
            $forwardUrl = $decryptedPayload['forward_url'] ?? null;
            if (!$forwardUrl) {
                $forwardUrl = $this->urlHelper->generate($this->defaultEntryRoute);
            }
            return new RedirectResponse($forwardUrl);
        }

        return new JsonResponse(
            [
                'error' => 'authentication_failed',
                'messages' => $authenticationResult->getMessages(),
            ],
            401
        );
    }

    protected function decryptPayload(string $payload): array|null
    {
        if (Env::get('R_DECRYPTION_KEY') === null) {
            return null;
        }
        try {
            $result = Crypto::decrypt($payload, Key::loadFromAsciiSafeString(Env::get('R_DECRYPTION_KEY')));
            return json_decode($result, true);
        } catch (\Throwable) {
        }
        return null;
    }
}