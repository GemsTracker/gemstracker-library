<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage SnippetsLoader
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\SnippetsLoader;

use Gems\FullHtmlResponse;
use Gems\Layout\LayoutRenderer;
use Gems\Layout\LayoutSettings;
use Gems\MenuNew\MenuSnippetHelper;
use Gems\Middleware\MenuMiddleware;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Session\SessionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zalt\Base\RequestInfo;
use Zalt\SnippetsLoader\MezzioLaminasSnippetResponder;
use Zalt\SnippetsLoader\SnippetLoaderInterface;

/**
 *
 * @package    Gems
 * @subpackage SnippetsLoader
 * @since      Class available since version 1.9.2
 */
class GemsSnippetResponder extends MezzioLaminasSnippetResponder
{
    protected LayoutSettings $layoutSettings;

    protected MenuSnippetHelper $menuHelper;
    
    public function __construct(
        SnippetLoaderInterface $snippetLoader,
        protected LayoutRenderer $layoutRenderer
    ) {
        parent::__construct($snippetLoader);
    }
    
    public function getSnippetsResponse(array $snippetNames, mixed $snippetOptions = [], ?ServerRequestInterface $request = null) : ResponseInterface
    {
        $output = parent::getSnippetsResponse($snippetNames, $snippetOptions, $request);

        if (
            ! $output instanceof HtmlResponse
            || $output instanceof FullHtmlResponse
            || $this->request->hasHeader('X-Content-Only')
        ) {
            return $output;
        }

        $breadcrumbs = array_reverse($this->menuHelper->getCurrentParentUrls(10));
        $breadcrumbs[] = ['label' =>  $this->menuHelper->getCurrentLabel()];
        $data = [
            'breadcrumbs' => $breadcrumbs,
            'content' => $output->getBody(),
        ];
        $statusCode = 200;
        $headers = [];

        if ($this->layoutRenderer) {
            return new HtmlResponse($this->layoutRenderer->render($this->layoutSettings, $this->request, $data), $statusCode, $headers);
        }
        
        return $output;
    }

    public function processRequest(ServerRequestInterface $request): RequestInfo
    {
        $requestInfo = parent::processRequest($request);

        $menu = $request->getAttribute(MenuMiddleware::MENU_ATTRIBUTE);

        if ($menu) {
            $this->menuHelper = new MenuSnippetHelper($menu, $requestInfo);
            
            $this->snippetLoader->addConstructorVariable(MenuSnippetHelper::class, $this->menuHelper);
        }

        $this->layoutSettings = new LayoutSettings();
        $this->layoutSettings->setTemplate( 'gems::legacy-view');
        $this->snippetLoader->addConstructorVariable(LayoutSettings::class, $this->layoutSettings);
        
        $this->snippetLoader->addConstructorVariable(SessionInterface::class, $request->getAttribute(SessionInterface::class));
        $this->snippetLoader->addConstructorVariable(ServerRequestInterface::class, $request);

        return $requestInfo;
    }
}