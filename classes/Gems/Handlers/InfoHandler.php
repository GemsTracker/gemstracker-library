<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage Handlers
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Handlers;

use Gems\Layout\LayoutRenderer;
use Gems\Layout\LayoutSettings;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Flash\FlashMessageMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zalt\Html\Html;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 *
 * @package    Gems
 * @subpackage Handlers
 * @since      Class available since version 1.9.2
 */
class InfoHandler implements RequestHandlerInterface
{
    public function __construct(protected LayoutRenderer $layoutRenderer, protected SnippetResponderInterface $responder) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->responder->processRequest($request);
        
        $output = $this->responder->getSnippetsResponse(['InfoSnippet']);
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
    }

}