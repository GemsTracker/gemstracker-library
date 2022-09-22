<?php

namespace Gems\Handlers;

use Gems\Locale\LocaleCookie;
use Gems\Middleware\LocaleMiddleware;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Helper\UrlHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ChangeLanguageHandler implements RequestHandlerInterface
{
    public function __construct(private UrlHelper $urlHelper, private LocaleCookie $localeCookie)
    {
    }


    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $url = $this->getUrl($request);
        $language = $request->getAttribute('language');
        $response = new RedirectResponse($url);
        $response = $this->localeCookie->addLocaleCookieToResponse($response, $language);
        return $response;
    }

    protected function getUrl(ServerRequestInterface $request): string
    {
        $encodedUrl = $request->getAttribute('url');
        if ($encodedUrl === null) {
            return $this->urlHelper->generate('/');
        }

        return base64_decode(urldecode($encodedUrl));
    }
}