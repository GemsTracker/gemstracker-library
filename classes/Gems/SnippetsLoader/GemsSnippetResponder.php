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
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
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
        protected LayoutRenderer $layoutRenderer
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
            return new HtmlResponse($this->layoutRenderer->render($layoutSettings, $request, $data), $statusCode, $headers);
        }
        
        return $output;
    }

}