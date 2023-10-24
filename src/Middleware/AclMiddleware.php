<?php

declare(strict_types=1);

namespace Gems\Middleware;

use Gems\AuthNew\AuthenticationMiddleware;
use Gems\Layout\LayoutRenderer;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Permissions\Acl\Acl;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AclMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly Acl $acl,
        private readonly LayoutRenderer $layoutRenderer,
        private readonly array $config,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var \Mezzio\Router\RouteResult $routeResult */
        $routeResult = $request->getAttribute('Mezzio\Router\RouteResult');
        $route = $routeResult->getMatchedRoute();
        $options = $route->getOptions();

        $userRole = null;
        $user = $request->getAttribute(AuthenticationMiddleware::CURRENT_USER_ATTRIBUTE);
        if ($user) {
            $userRole = $user->getRole();
        }

        $disablePrivileges = isset($this->config['temp_config']['disable_privileges']) && $this->config['temp_config']['disable_privileges'] === true;

        if (
            !empty($options['privilege']) && (
                $userRole === null
                || !$this->acl->isAllowed($userRole, $options['privilege'])
            ) && !$disablePrivileges
        ) {
            return new HtmlResponse($this->layoutRenderer->renderTemplate('error::404', $request), 404);
        }

        return $handler->handle($request);
    }
}
