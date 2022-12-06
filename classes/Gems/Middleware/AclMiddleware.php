<?php

declare(strict_types=1);

namespace Gems\Middleware;

use Gems\AuthNew\AuthenticationMiddleware;
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
        private readonly TemplateRendererInterface $template,
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

        if (
            !empty($options['privilege']) && (
                $userRole === null
                || !$this->acl->isAllowed($userRole, $options['privilege'])
            ) && !$this->config['temp_config']['disable_privileges']
        ) {
            return new HtmlResponse($this->template->render('error::404'), 404);
        }

        return $handler->handle($request);
    }
}
