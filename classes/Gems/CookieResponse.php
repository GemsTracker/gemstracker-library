<?php

namespace Gems;

use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\Modifier\SameSite;
use Dflydev\FigCookies\SetCookie;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class CookieResponse
{
    protected static array $defaultOptions = [
        'httpOnly' => true,
        'sameSite' => 'strict', // strict, lax or none
        'path' => '/',
        // 'domain' => '',
        // 'expires' => '' // int|string|DateTimeInterface
    ];

    public static function addCookieToResponse(ServerRequestInterface $request, ResponseInterface $response, string $key, mixed $value, array $options = []): ResponseInterface
    {
        if (empty($options)) {
            $options = static::$defaultOptions;
        }

        $cookie = SetCookie::create($key)
            ->withValue($value)
            ->withoutSameSite();

        if (isset($options['httpOnly']) && $options['httpOnly'] === true) {
            $cookie = $cookie->withHttpOnly();
        }
        if (isset($options['sameSite'])) {
            switch ($options['sameSite']) {
                case 'strict':
                    $cookie = $cookie->withSameSite(SameSite::strict());
                    break;
                case 'lax':
                    $cookie = $cookie->withSameSite(SameSite::lax());
                    break;
                case 'none':
                    $cookie = $cookie->withSameSite(SameSite::none());
                    break;
                default:
                    break;
            }
        }

        if (isset($options['path'])) {
            $cookie = $cookie->withPath($options['path']);
        }

        if (isset($options['domain'])) {
            $cookie = $cookie->withDomain($options['domain']);
        }

        if (isset($options['expires'])) {
            $cookie = $cookie->withExpires($options['expires']);
        }

        if (isset($options['maxAge'])) {
            $cookie = $cookie->withMaxAge($options['maxAge']);
        }

        // TODO: Check for secure connection
        // $cookie = $cookie->withSecure();

        $response = FigResponseCookies::set($response, $cookie);

        return $response;
    }
}