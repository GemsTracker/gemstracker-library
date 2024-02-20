<?php

namespace Gems\Middleware;

use Gems\AuthNew\Adapter\AuthenticationIdentityInterface;
use Gems\AuthNew\AuthenticationMiddleware;
use Gems\Legacy\CurrentUserRepository;
use Gems\Repository\OrganizationRepository;
use Gems\Site\SiteUrl;
use Gems\User\User;
use Gems\User\UserLoader;
use Laminas\Diactoros\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CurrentOrganizationMiddleware implements MiddlewareInterface
{
    public const CURRENT_ORGANIZATION_ATTRIBUTE = 'current_organization';
    public const CURRENT_ORGANIZATION_CODE_ATTRIBUTE = 'current_organization_code';

    public function __construct(
        protected OrganizationRepository $organizationRepository,
        protected CurrentUserRepository $currentUserRepository,
    )
    {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $currentOrganizationId = $this->getCurrentOrganizationId($request);

        $currentOrganization = $this->organizationRepository->getOrganization($currentOrganizationId);

        /** @var ServerRequest $request */
        $request = $request
            ->withAttribute(self::CURRENT_ORGANIZATION_ATTRIBUTE, $currentOrganizationId)
            ->withAttribute(self::CURRENT_ORGANIZATION_CODE_ATTRIBUTE, $currentOrganization->getCode() ?? '');


        /**
         * @var User|null $user
         */
        $user = $request->getAttribute(AuthenticationMiddleware::CURRENT_USER_ATTRIBUTE);
        if ($user instanceof User) {
            $user->setCurrentOrganizationId($currentOrganizationId);
        }

        $this->currentUserRepository->setCurrentOrganizationId($currentOrganizationId);

        return $handler->handle($request);
    }

    protected function getCurrentOrganizationId(ServerRequestInterface $request): int
    {
        $currentOrganizationId = null;

        // First check cookie
        $cookies = $request->getCookieParams();
        if (isset($cookies[static::CURRENT_ORGANIZATION_ATTRIBUTE])) {
            $currentOrganizationId = $cookies[static::CURRENT_ORGANIZATION_ATTRIBUTE];
        }

        $identity = $request->getAttribute(AuthenticationMiddleware::CURRENT_IDENTITY_ATTRIBUTE);
        if ($identity instanceof AuthenticationIdentityInterface) {
            $baseOrganizationId = $identity->getOrganizationId();

            if ($currentOrganizationId) {
                $user = $request->getAttribute('current_user');
                if ($user instanceof User && $user->isActive()) {
                    $allowedOrganizations = $user->getAllowedOrganizations();
                } else {
                    $allowedOrganizations = $this->organizationRepository->getAllowedOrganizationsFor(
                        $baseOrganizationId
                    );
                }
                if (isset($allowedOrganizations[$currentOrganizationId])) {
                    return $currentOrganizationId;
                }
            }

            return $baseOrganizationId;
        }

        $siteUrl = $request->getAttribute(SiteGateMiddleware::SITE_URL_ATTRIBUTE);
        if ($siteUrl instanceof SiteUrl && $siteUrl->getFirstOrganizationId()) {
            if ($currentOrganizationId && in_array($currentOrganizationId, $siteUrl->getOrganizations())) {
                return $currentOrganizationId;
            }
            return $siteUrl->getFirstOrganizationId();
        }

        return UserLoader::SYSTEM_NO_ORG;
    }
}