<?php

declare(strict_types=1);

namespace Gems\Middleware;

use Gems\Acl\Privilege;
use Gems\AuthNew\AuthenticationMiddleware;
use Gems\Exception\AuthenticationException;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Permissions\Acl\Acl;
use Mezzio\Router\RouteResult;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AclMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly Acl $acl,
        private readonly TemplateRendererInterface $template,
        private readonly array $config,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        /** @var RouteResult $routeResult */
        $routeResult = $request->getAttribute(RouteResult::class);
        $route = $routeResult->getMatchedRoute();
        $options = $route->getOptions();

        $userRole = null;
        $user = $request->getAttribute(AuthenticationMiddleware::CURRENT_USER_ATTRIBUTE);
        if ($user) {
            $userRole = $user->getRole();
        }

        $privilege = $options['privilege'] ?? null;

        if (!$this->isAllowedRequest($request, $userRole, $privilege)) {
            return new HtmlResponse($this->template->render('error::404'), 404);
        }

        return $handler->handle($request);
    }

    protected function isAllowedRequest(ServerRequestInterface $request, ?string $userRole, string|Privilege|null $privilege): bool
    {
        $resource = $privilege;
        if ($privilege instanceof Privilege) {
            try {
                $resource = $privilege->getName($request->getMethod());
            } catch (AuthenticationException) {
                return false;
            }
        }
        if (empty($resource)) {
            return true;
        }
        if (isset($this->config['temp_config'], $this->config['temp_config']['disable_privileges'])
            && $this->config['temp_config']['disable_privileges'] === true) {
            return true;
        }

        if ($userRole === null) {
            return false;
        }

        if ($this->acl->isAllowed($userRole, $resource)) {
            return true;
        }

        return false;
    }
}
