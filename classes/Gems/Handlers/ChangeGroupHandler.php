<?php

namespace Gems\Handlers;

use Gems\AuthNew\AuthenticationMiddleware;
use Gems\Site\SiteUtil;
use Gems\User\User;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Helper\UrlHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ChangeGroupHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly SiteUtil $siteUtil,
        private readonly UrlHelper $urlHelper,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();

        if (isset($queryParams['group'])) {
            /** @var User $currentUser */
            $currentUser = $request->getAttribute(AuthenticationMiddleware::CURRENT_USER_ATTRIBUTE);

            // Throws exception on invalid value
            $currentUser->setGroupTemp(intval($queryParams['group']));
        }

        return new RedirectResponse($this->getUrl($request));
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