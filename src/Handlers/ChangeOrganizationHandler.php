<?php

namespace Gems\Handlers;

use Gems\AuthNew\Adapter\AuthenticationIdentityInterface;
use Gems\AuthNew\AuthenticationMiddleware;
use Gems\CookieResponse;
use Gems\Middleware\CurrentOrganizationMiddleware;
use Gems\Repository\OrganizationRepository;
use Gems\Site\SiteUtil;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Helper\UrlHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ChangeOrganizationHandler implements RequestHandlerInterface
{
    public function __construct(private SiteUtil $siteUtil, private UrlHelper $urlHelper, private OrganizationRepository $organizationRepository)
    {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $url = $this->getUrl($request);
        $response = new RedirectResponse($url);
        $queryParams = $request->getQueryParams();

        if (isset($queryParams['org'])) {
            /**
             * @var $identity AuthenticationIdentityInterface
             */
            $identity = $request->getAttribute(AuthenticationMiddleware::CURRENT_IDENTITY_ATTRIBUTE);
            $baseOrganizationId = $identity->getOrganizationId();
            $allowedOrganizations = $this->organizationRepository->getAllowedOrganizationsFor($baseOrganizationId);
            if (isset($allowedOrganizations[$queryParams['org']])) {
                $response = CookieResponse::addCookieToResponse(
                    $request,
                    $response,
                    CurrentOrganizationMiddleware::CURRENT_ORGANIZATION_ATTRIBUTE,
                    $queryParams['org']
                );
            }
        }

        return $response;
    }

    protected function getUrl(ServerRequestInterface $request): string
    {
        $serverParams = $request->getServerParams();
        if (isset($serverParams['HTTP_REFERER']) && $this->siteUtil->isAllowedUrl($serverParams['HTTP_REFERER'])) {
            return $serverParams['HTTP_REFERER'];
        }

        return $this->urlHelper->generate('/');
    }
}