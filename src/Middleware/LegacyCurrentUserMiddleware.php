<?php

namespace Gems\Middleware;

use Gems\Legacy\CurrentUserRepository;
use Gems\Model;
use Gems\User\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LegacyCurrentUserMiddleware implements MiddlewareInterface
{

    public function __construct(protected CurrentUserRepository $currentUserRepository)
    {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $currentUser = $request->getAttribute('current_user');

        if ($currentUser instanceof User && $currentUser->isActive()) {

            $this->currentUserRepository->setCurrentUser($currentUser);
            Model::setCurrentUserId($currentUser->getUserId());
        }

        return $handler->handle($request);
    }
}