<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Csrf
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Csrf;

use Gems\AuthNew\AuthenticationMiddleware;
use Mezzio\Csrf\CsrfGuardInterface;
use Mezzio\Csrf\FlashCsrfGuardFactory;
use Mezzio\Csrf\SessionCsrfGuardFactory;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @package    Gems
 * @subpackage Csrf
 * @since      Class available since version 1.0
 */
class GemsCsrfGuardFactory implements \Mezzio\Csrf\CsrfGuardFactoryInterface
{
    public function __construct(
        protected readonly FlashCsrfGuardFactory $flashCsrfGuardFactory,
        protected readonly SessionCsrfGuardFactory $sessionCsrfGuardFactory,
    )
    {
    }

    public function createGuardFromRequest(ServerRequestInterface $request): CsrfGuardInterface
    {
        if ($request->getAttribute(AuthenticationMiddleware::CURRENT_USER_ATTRIBUTE)) {
            return $this->sessionCsrfGuardFactory->createGuardFromRequest($request);
        }
        return $this->flashCsrfGuardFactory->createGuardFromRequest($request);
    }
}