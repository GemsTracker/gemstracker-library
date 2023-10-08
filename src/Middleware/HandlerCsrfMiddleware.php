<?php

declare(strict_types=1);

namespace Gems\Middleware;

use Exception;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Csrf\CsrfGuardInterface;
use Mezzio\Csrf\CsrfMiddleware;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\StatusMessengerInterface;

class HandlerCsrfMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    public static function getTokenName(string $controller, string $action)
    {
        return strtolower(strtr(sprintf('__csrf_%s_%s', $controller, str_replace('-', '', $action)), '\\', '_'));
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getMethod() === 'POST') {
            /** @var CsrfGuardInterface $csrfGuard */
            $csrfGuard = $request->getAttribute(CsrfMiddleware::GUARD_ATTRIBUTE);

            /** @var StatusMessengerInterface $flash */
            $flash = $request->getAttribute(FlashMessageMiddleware::STATUS_MESSENGER_ATTRIBUTE);

            if (!$csrfGuard || !$flash) {
                throw new Exception('HandlerCsrfMiddleware requires CSRF guard and flash messages middleware');
            }

            $tokenName = '__csrf';
            $routeResult = $request->getAttribute(RouteResult::class);
            if ($routeResult instanceof RouteResult) {
                $route = $routeResult->getMatchedRoute();
                if ($route instanceof Route) {
                    $options   = $route->getOptions();
                    if (isset($options['controller'], $options['action'])) {
                        $tokenName = self::getTokenName($options['controller'], $options['action']);
                    }
                }
            }
            $inputToken = $request->getParsedBody()[$tokenName] ?? '';

            if (empty($inputToken) || !$csrfGuard->validateToken($inputToken, $tokenName)) {
                $flash->addError($this->translator->trans('The form has expired, please try again.'));
                return new RedirectResponse($request->getUri());
            }
        }

        return $handler->handle($request);
    }
}
