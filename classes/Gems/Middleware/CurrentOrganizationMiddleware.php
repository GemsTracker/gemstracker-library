<?php

namespace Gems\Middleware;

use Gems\AuthNew\Adapter\AuthenticationIdentityInterface;
use Gems\AuthNew\AuthenticationMiddleware;
use Gems\Repository\OrganizationRepository;
use Gems\User\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CurrentOrganizationMiddleware implements MiddlewareInterface
{
    public const CURRENT_ORGANIZATION_ATTRIBUTE = 'current_organization';

    public function __construct(protected OrganizationRepository $organizationRepository)
    {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $currentOrganizationId = $this->getCurrentOrganizationId($request);

        $request = $request->withAttribute(static::CURRENT_ORGANIZATION_ATTRIBUTE, $currentOrganizationId);

        /**
         * @var $user User
         */
        $user = $request->getAttribute(AuthenticationMiddleware::CURRENT_USER_ATTRIBUTE);
        $user->setCurrentOrganization($currentOrganizationId);

        return $handler->handle($request);
    }

    protected function getCurrentOrganizationId(ServerRequestInterface $request): int
    {
        /**
         * @var $identity AuthenticationIdentityInterface
         */
        $identity = $request->getAttribute(AuthenticationMiddleware::CURRENT_IDENTITY_ATTRIBUTE);
        $baseOrganizationId = $identity->getOrganizationId();

        // First check cookie
        $cookies = $request->getCookieParams();
        if (isset($cookies[static::CURRENT_ORGANIZATION_ATTRIBUTE])) {
            $currentOrganizationId = $cookies[static::CURRENT_ORGANIZATION_ATTRIBUTE];
            $allowedOrganizations = $this->organizationRepository->getAllowedOrganizationsFor($baseOrganizationId);
            if (isset($allowedOrganizations[$currentOrganizationId])) {
                return $currentOrganizationId;
            }
        }

        return $baseOrganizationId;
    }
}