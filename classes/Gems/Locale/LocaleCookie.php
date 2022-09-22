<?php

namespace Gems\Locale;

use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\Modifier\SameSite;
use Dflydev\FigCookies\SetCookie;
use Gems\Middleware\LocaleMiddleware;
use Psr\Http\Message\ResponseInterface;

class LocaleCookie
{
    public function addLocaleCookieToResponse(ResponseInterface $response, $language): ResponseInterface
    {
        $response = FigResponseCookies::set($response, SetCookie::create(LocaleMiddleware::LOCALE_ATTRIBUTE)
            ->withValue($language)
            ->withHttpOnly()
            ->withSameSite(SameSite::strict())
            //->withDomain('example.com')
            ->withPath('/'));

        return $response;
    }
}