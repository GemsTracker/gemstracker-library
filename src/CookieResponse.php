<?php

namespace Gems;

use DateTimeInterface;
use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\Modifier\SameSite;
use Dflydev\FigCookies\SetCookie;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zalt\Base\BaseDir;
use Zalt\Base\RequestUtil;

class CookieResponse
{
    protected static array $defaultOptions = [
        'httpOnly' => true,
        'sameSite' => 'lax', // strict, lax or none
        'path' => '/',
        // 'domain' => '',
        // 'expires' => '' // int|string|DateTimeInterface
    ];

    public static function addCookieToResponse(
        ServerRequestInterface $request,
        ResponseInterface $response,
        string $key,
        mixed $value,
        bool $httpOnly = true,
        string $sameSite = 'lax',
        string|null $path = null,
        int $maxAge = 90 * 86400, // 90 days
        DateTimeInterface|null $expires = null,
        string|null $domain = null,
    ): ResponseInterface
    {
        $options = static::$defaultOptions;

        $cookie = SetCookie::create($key)
            ->withValue($value)
            ->withoutSameSite();

        if ($httpOnly) {
            $cookie = $cookie->withHttpOnly();
        }

        switch ($sameSite) {
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

        if ($path === null) {
            $path = BaseDir::getBaseDir();
        }
        $cookie = $cookie->withPath($path);

        if ($domain !== null) {
            $cookie = $cookie->withDomain($domain);
        }

        if ($expires !== null) {
            $cookie = $cookie->withExpires($expires);
        }

        $cookie = $cookie->withMaxAge($maxAge);

        if (RequestUtil::isSecure($request)) {
            $cookie = $cookie->withSecure();
        }

        return FigResponseCookies::set($response, $cookie);
    }
}