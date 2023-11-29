<?php

declare(strict_types=1);


/**
 * @package    Gems
 * @subpackage Handlers
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Handlers;

use Mezzio\Session\SessionInterface;
use Zalt\Html\Paginator\PaginatorInterface;

/**
 * @package    Gems
 * @subpackage Handlers
 * @since      Class available since version 1.0
 */
trait PaginatorHandlerTrait
{
    use CookieHandlerTrait;

    public function getDynamicSortFor(string $sortDescParam, string $sortAscParam): array
    {
        $requestSort = [];
        $session     = $this->getSession();
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

        if (isset($cookies[PaginatorInterface::REQUEST_ITEMS])) {
            $currentItems = max(intval($cookies[PaginatorInterface::REQUEST_ITEMS]), 5);
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
}