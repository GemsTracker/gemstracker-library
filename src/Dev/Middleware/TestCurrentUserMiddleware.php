<?php

namespace Gems\Dev\Middleware;

use Gems\Legacy\CurrentUserRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class TestCurrentUserMiddleware implements \Psr\Http\Server\MiddlewareInterface
{
    protected CurrentUserRepository $currentUserRepository;
    protected array $config = [];

    public function __construct(CurrentUserRepository $currentUserRepository, array $config)
    {
        $this->currentUserRepository = $currentUserRepository;

        if (isset($config['dev'])) {
            $this->config = $config['dev'];
        }
    }

    protected function getCurrentUser(): \Gems\User\User
    {
        $currentUsername = null;
        $currentOrganizationId = null;
        if (isset($this->config['currentUsername'], $this->config['currentOrganizationId'])) {
            $currentUsername = $this->config['currentUsername'];
            $currentOrganizationId = $this->config['currentOrganizationId'];
        }

        $this->currentUserRepository->setCurrentUserCredentials($currentUsername, $currentOrganizationId);

        return $this->currentUserRepository->getCurrentUser();
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $currentUser = $this->getCurrentUser();
        $request = $request->withAttribute('userId', $currentUser->getUserId())
            ->withAttribute('userName', $currentUser->getLoginName())
            ->withAttribute('userOrganization', $currentUser->getBaseOrganizationId())
            ->withAttribute('userRole', $currentUser->getRole());

        return $handler->handle($request);
    }
}