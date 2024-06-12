<?php

namespace Gems\Handlers\Auth;

use Gems\AuthNew\Adapter\AccessTokenAuthentication;
use Gems\AuthNew\AuthenticationServiceBuilder;
use Gems\User\UserLoader;
use Laminas\Diactoros\Response\JsonResponse;
use Laminas\Diactoros\Response\RedirectResponse;
use League\OAuth2\Server\ResourceServer;
use Mezzio\Helper\UrlHelper;
use Mezzio\Session\SessionMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AccessTokenSessionLoginHandler implements RequestHandlerInterface
{
    private string $payloadParamName = 'forward';

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
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);
        $authenticationService = $this->authenticationServiceBuilder->buildAuthenticationService($session);
        $authenticationResult = $authenticationService->authenticate(
            new AccessTokenAuthentication(
                $this->resourceServer,
                $request,
                $this->userLoader,
            )
        );

        if ($authenticationResult->isValid()) {
            return $this->getRedirectResponse($request);
        }

        return new JsonResponse(
            [
                'error' => 'authentication_failed',
                'messages' => $authenticationResult->getMessages(),
            ],
            401
        );
    }

    protected function getRedirectResponse(ServerRequestInterface $request): ResponseInterface
    {
        $forwardUrl = match ($request->getMethod()) {
            'POST' => $this->getForwardUrlFromPost($request),
            'GET' => $this->getForwardUrlFromGet($request),
            default => null,
        };

        if ($forwardUrl === null) {
            $forwardUrl = $this->urlHelper->generate($this->defaultEntryRoute);
        }

        return new RedirectResponse($forwardUrl);
    }

    protected function getForwardUrlFromGet(ServerRequestInterface $request): string|null
    {
        $params = $request->getQueryParams();
        if (isset($params[$this->payloadParamName])) {
            return base64_decode($params[$this->payloadParamName]);
        }
        return null;
    }

    protected function getForwardUrlFromPost(ServerRequestInterface $request): string|null
    {
        $rawBody = $request->getBody()->getContents();
        $body = json_decode($rawBody, true);
        return $body[$this->payloadParamName] ?? null;
    }
}