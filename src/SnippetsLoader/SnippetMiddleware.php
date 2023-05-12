<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage SnippetsLoader
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\SnippetsLoader;

use Gems\Menu\MenuSnippetHelper;
use Gems\Middleware\MenuMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zalt\Base\RequestInfoFactory;
use Zalt\SnippetsLoader\SnippetLoader;

/**
 *
 * @package    Gems
 * @subpackage SnippetsLoader
 * @since      Class available since version 1.9.2
 */
class SnippetMiddleware extends \Zalt\SnippetsLoader\SnippetMiddleware
{
    public function __construct(
        protected SnippetLoader $snippetLoader
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->requestInfo = RequestInfoFactory::getMezzioRequestInfo($request);

        $menu = $request->getAttribute(MenuMiddleware::MENU_ATTRIBUTE);

        if ($menu) {
            $menuHelper = new MenuSnippetHelper($menu, $this->requestInfo);

            $this->snippetLoader->addConstructorVariable(MenuSnippetHelper::class, $menuHelper);
        }

        return parent::process($request, $handler);
    }
}