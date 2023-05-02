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
use Zalt\Html\Paginator\PaginatorInterface;

/**
 * @package    Gems
 * @subpackage Handlers
 * @since      Class available since version 1.0
 */
trait PaginatorHandlerTrait
{
    protected array $_cookiesSet = [];

    /**
     * @var ServerRequestInterface
     */
    protected ServerRequestInterface $request;

    /**
     * @var RequestInfo
     */
    protected RequestInfo $requestInfo;

    public function addPageCookie(string $name, ?string $value = null, int $days = 90): void
    {
        $cookie = SetCookie::create($name, $value);
        $cookie = $cookie->withHttpOnly();
        $cookie = $cookie->withSameSite(SameSite::strict());
        $cookie = $cookie->withPath($this->requestInfo->getBasePath());
        $cookie = $cookie->withMaxAge($days * 86400000);

        $this->_cookiesSet[] = $cookie;
    }

    public function getDynamicSortFor(string $sortDescParam, string $sortAscParam): array
    {
        $requestSort = [];
        $session     = $this->request->getAttribute(SessionInterface::class);
        $sessionId   = $this->requestInfo->getBasePath() . '/dynamicSort';

        // Get (new) request sort DESC
        $fieldDesc = $this->requestInfo->getParam($sortDescParam);
        if ($fieldDesc) {
            $requestSort[$fieldDesc] = SORT_DESC;
        }

        // Get (new) request sort DESC
        $fieldAsc = $this->requestInfo->getParam($sortAscParam);
        if ($fieldAsc) {
            $requestSort[$fieldAsc] = SORT_ASC;
        }

        if ($session instanceof SessionInterface) {
            $sessionSort = $session->get($sessionId, []);

            if ($requestSort) {
                $sessionSort = $requestSort + $sessionSort;
            }
            $session->set($sessionId, $sessionSort);

            return $sessionSort;
        }

        return $requestSort;
    }


    public function getPageItems(): int
    {
        $cookies = $this->request->getCookieParams();
        dump($cookies);

        if (isset($cookies[PaginatorInterface::REQUEST_ITEMS])) {
            $currentItems = intval($cookies[PaginatorInterface::REQUEST_ITEMS]);
        } else {
            $currentItems = 10;
        }

        $resultItems = $this->getSessionRequestInt(PaginatorInterface::REQUEST_ITEMS, $currentItems);

        if ($resultItems != $currentItems) {
            $this->addPageCookie(PaginatorInterface::REQUEST_ITEMS, (string) $resultItems);
        }

        return $resultItems;
    }

    public function getPageNumber(): int
    {
        return $this->getSessionRequestInt(PaginatorInterface::REQUEST_PAGE, 1);
    }

    protected function getSessionRequestInt(string $requestId, int $default): int
    {
        $sessionId = $this->requestInfo->getBasePath() . '/' . $requestId;
        $session   = $this->request->getAttribute(SessionInterface::class);

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

    protected function processCookies(ResponseInterface $response): ResponseInterface
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