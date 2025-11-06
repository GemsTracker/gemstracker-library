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
use Gems\Menu\MenuSnippetHelper;
use Gems\Middleware\MenuMiddleware;
use Gems\Repository\EmbeddedUserRepository;
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
    public const DEFAULT_TEMPLATE = 'gems::legacy-view';

    protected MenuSnippetHelper $menuHelper;
    
    public function __construct(
        SnippetLoaderInterface                    $snippetLoader,
        protected readonly EmbeddedUserRepository $embeddedUserRepository,
        protected readonly LayoutRenderer         $layoutRenderer,
        protected readonly LayoutSettings         $layoutSettings,
    ) {
        parent::__construct($snippetLoader);
    }

    public function getLayoutSettings(): LayoutSettings
    {
        return $this->layoutSettings;
    }

    public function getMenuSnippetHelper(): ?MenuSnippetHelper
    {
        if (isset($this->menuHelper)) {
            return $this->menuHelper;
        }
        return null;
    }

    public function getSnippetsResponse(array $snippetNames, mixed $snippetOptions = [], ?ServerRequestInterface $request = null) : ResponseInterface
    {
        $output = parent::getSnippetsResponse($snippetNames, $snippetOptions, $request);

        if (
            (! $output instanceof HtmlResponse)
            || $this->request->hasHeader('X-Content-Only')
        ) {
            return $output;
        }

        $data = [
            'content' => $output->getBody(),
        ];

        if (isset($this->menuHelper)) {
            $breadcrumbs = array_reverse($this->menuHelper->getCurrentParentUrls(10));
            $breadcrumbs[] = ['label' => $this->menuHelper->getCurrentLabel()];
            $data['breadcrumbs'] = $breadcrumbs;
            $data['title_breadcrumbs'] = ' | ' . implode(' - ', array_column($breadcrumbs, 'label'));
        }
        $statusCode = 200;
        $headers = [];

        if ($this->layoutRenderer) {
            return new HtmlResponse($this->layoutRenderer->render($this->layoutSettings, $this->request, $data), $statusCode, $headers);
        }
        
        return $output;
    }

    public function processRequest(ServerRequestInterface $request): RequestInfo
    {
        $this->embeddedUserRepository->checkRequest($request);

        $requestInfo = parent::processRequest($request);

        $menu = $request->getAttribute(MenuMiddleware::MENU_ATTRIBUTE);

        if ($menu) {
            $this->menuHelper = new MenuSnippetHelper($menu, $requestInfo);
            
            $this->snippetLoader->addConstructorVariable(MenuSnippetHelper::class, $this->menuHelper);
        }

        if ($this->embeddedUserRepository->hasEmbeddedData()) {
            $template = $this->embeddedUserRepository->getTemplate();
        } else {
            $template = $request->getAttribute(LayoutSettings::TEMPLATE_ATTRIBUTE, self::DEFAULT_TEMPLATE);
        }

        $this->layoutSettings->setTemplate($template);
        $this->snippetLoader->addConstructorVariable(LayoutSettings::class, $this->layoutSettings);
        
        $this->snippetLoader->addConstructorVariable(SessionInterface::class, $request->getAttribute(SessionInterface::class));
        $this->snippetLoader->addConstructorVariable(ServerRequestInterface::class, $request);

        return $requestInfo;
    }
}