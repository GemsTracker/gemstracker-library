<?php

declare(strict_types=1);


/**
 * @package    Gems
 * @subpackage Handlers
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Handlers;

use Dflydev\FigCookies\FigResponseCookies;
use Dflydev\FigCookies\Modifier\SameSite;
use Dflydev\FigCookies\SetCookie;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zalt\Base\RequestInfo;

/**
 * @package    Gems
 * @subpackage Handlers
 * @since      Class available since version 1.0
 */
trait CookieHandlerTrait
{
    /**
     * @var SetCookie[]
     */
    protected array $_cookiesSet = [];

    /**
     * @var ServerRequestInterface
     */
    protected ServerRequestInterface $request;

    /**
     * @var RequestInfo
     */
    protected RequestInfo $requestInfo;

    protected function _addCookie(string $name, ?string $value, int $days, string $path): void
    {
        $cookie = SetCookie::create($name, $value);
        $cookie = $cookie->withHttpOnly();
        $cookie = $cookie->withSameSite(SameSite::strict());
        $cookie = $cookie->withPath($path);
        $cookie = $cookie->withMaxAge($days * 86400000);

        $this->_cookiesSet[] = $cookie;
    }

    public function addPageCookie(string $name, ?string $value = null, int $days = 90): void
    {
        $this->_addCookie($name, $value, $days, $this->requestInfo->getCurrentPage());
    }

    public function addSiteCookie(string $name, ?string $value = null, int $days = 90): void
    {
        $this->_addCookie($name, $value, $days, '/');
    }

    protected function getSession(): ?SessionInterface
    {
        return $this->request->getAttribute(SessionInterface::class);
    }

    protected function getSessionRequestInt(string $requestId, int $default): int
    {
        $sessionId = $this->requestInfo->getBasePath() . '/' . $requestId;
        $session   = $this->getSession();

        $value = $this->requestInfo->getParam($requestId);
        if ($value) {
            $value = intval($value);
            if ($session instanceof SessionInterface) {
                $session->set($sessionId, $value);
            }
            return $value;
        }

        if ($session instanceof SessionInterface && $session->has($sessionId)) {
            return intval($session->get($sessionId));
        }

        return $default;
    }

    protected function processResponseCookies(ResponseInterface $response): ResponseInterface
    {
        if ($this->_cookiesSet) {
            foreach ($this->_cookiesSet as $cookie) {
                if ($cookie instanceof SetCookie) {
                    $response = FigResponseCookies::set($response, $cookie);
                }
            }
        }

        return $response;
    }
}