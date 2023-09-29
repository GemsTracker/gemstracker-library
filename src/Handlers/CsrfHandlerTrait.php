<?php

declare(strict_types=1);


/**
 * @package    Gems
 * @subpackage Handlers
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Handlers;

use Gems\Middleware\HandlerCsrfMiddleware;
use Mezzio\Csrf\CsrfGuardInterface;
use Mezzio\Csrf\CsrfMiddleware;
use Zalt\Base\RequestInfo;

/**
 * @package    Gems
 * @subpackage Handlers
 * @since      Class available since version 1.0
 */
trait CsrfHandlerTrait
{
    protected RequestInfo $requestInfo;

    public function getCsrfToken(string $tokenName = null)
    {
        /** @var CsrfGuardInterface $csrfGuard */
        $csrfGuard = $this->request->getAttribute(CsrfMiddleware::GUARD_ATTRIBUTE);
        return $csrfGuard->generateToken($tokenName ?: $this->getCsrfTokenName());
    }

    public function getCsrfTokenName(): string
    {
        return HandlerCsrfMiddleware::getTokenName(
            $this->requestInfo->getCurrentController(),
            $this->requestInfo->getCurrentAction()
        );
    }
}