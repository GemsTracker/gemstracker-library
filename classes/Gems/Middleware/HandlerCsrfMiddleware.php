<?php

declare(strict_types=1);

namespace Gems\Middleware;

use Gems\DecoratedFlashMessagesInterface;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Csrf\CsrfGuardInterface;
use Mezzio\Csrf\CsrfMiddleware;
use Mezzio\Flash\FlashMessageMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class HandlerCsrfMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getMethod() === 'POST') {
            /** @var CsrfGuardInterface $csrfGuard */
            $csrfGuard = $request->getAttribute(CsrfMiddleware::GUARD_ATTRIBUTE);

            /** @var DecoratedFlashMessagesInterface $flash */
            $flash = $request->getAttribute(FlashMessageMiddleware::FLASH_ATTRIBUTE);

            if (!$csrfGuard || !$flash) {
                throw new \Exception('HandlerCsrfMiddleware requires CSRF guard and flash messages middleware');
            }

            $inputToken = $request->getParsedBody()['__csrf'] ?? '';
            if (empty($inputToken) || !$csrfGuard->validateToken($inputToken)) {
                $flash->flashError($this->translator->trans('The form has expired, please try again.'));
                return new RedirectResponse($request->getUri());
            }
        }

        return $handler->handle($request);
    }
}
