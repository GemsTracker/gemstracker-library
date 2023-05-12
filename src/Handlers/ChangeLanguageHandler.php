<?php

namespace Gems\Handlers;

use Gems\CookieResponse;
use Gems\Locale\LocaleCookie;
use Gems\Middleware\LocaleMiddleware;
use Gems\Site\SiteUtil;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Helper\UrlHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ChangeLanguageHandler implements RequestHandlerInterface
{
    public function __construct(private SiteUtil $siteUtil, private UrlHelper $urlHelper)
    {
    }


    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $url = $this->getUrl($request);
        $language = $request->getAttribute('language');
        $response = new RedirectResponse($url);
        return CookieResponse::addCookieToResponse($request, $response, LocaleMiddleware::LOCALE_ATTRIBUTE, $language);
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