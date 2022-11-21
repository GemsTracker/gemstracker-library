<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage SnippetsLoader
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\SnippetsLoader;

use Gems\Layout\LayoutRenderer;
use Gems\Layout\LayoutSettings;
use Gems\MenuNew\Menu;
use Gems\MenuNew\MenuUrlRouter;
use Gems\MenuNew\RouteHelper;
use Gems\Middleware\MenuMiddleware;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zalt\Base\RequestInfo;
use Zalt\Html\Routes\UrlRoutes;
use Zalt\SnippetsLoader\MezzioLaminasSnippetResponder;
use Zalt\SnippetsLoader\SnippetLoader;

/**
 *
 * @package    Gems
 * @subpackage SnippetsLoader
 * @since      Class available since version 1.9.2
 */
class GemsSnippetResponder extends MezzioLaminasSnippetResponder
{
    public function __construct(
        protected SnippetLoader $snippetLoader,
        protected LayoutRenderer $layoutRenderer,
        protected RouteHelper $routeHelper 
    ) {
    }
    
    public function getSnippetsResponse(array $snippetNames, mixed $snippetOptions = [], ?ServerRequestInterface $request = null) : ResponseInterface
    {
        $output = parent::getSnippetsResponse($snippetNames, $snippetOptions, $request);

        if (! $output instanceof HtmlResponse) {
            return $output;
        }

        $data = [
            'content' => $output->getBody(),
        ];
        $statusCode = 200;
        $headers = [];

        if ($this->layoutRenderer) {
            $layoutSettings = new LayoutSettings();
            $layoutSettings->setTemplate( 'gems::legacy-view');
            return new HtmlResponse($this->layoutRenderer->render($layoutSettings, $this->request, $data), $statusCode, $headers);
        }
        
        return $output;
    }

    public function processRequest(ServerRequestInterface $request): RequestInfo
    {
        $requestInfo = parent::processRequest($request);

        $menu = $request->getAttribute(MenuMiddleware::MENU_ATTRIBUTE);

        if ($menu) {
            UrlRoutes::setUrlRouter(new MenuUrlRouter($menu, $this->routeHelper, $requestInfo));
        }
        
        return $requestInfo;
    }
}