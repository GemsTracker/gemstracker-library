<?php

declare(strict_types=1);

namespace Gems\Handlers;

use Fig\Http\Message\StatusCodeInterface;
use Gems\Layout\LayoutRenderer;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Mezzio\Handler\NotFoundHandler as BaseNotFoundHandler;

use function sprintf;

class NotFoundHandler extends BaseNotFoundHandler
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly LayoutRenderer $layoutRenderer,
        private readonly ?TemplateRendererInterface $renderer = null,
        private readonly string $template = self::TEMPLATE_DEFAULT,
        private readonly string $layout = self::LAYOUT_DEFAULT,
    ) {
        parent::__construct($responseFactory, $this->renderer, $this->template, $this->layout);
    }

    /**
     * Creates and returns a 404 response.
     *
     * @param ServerRequestInterface $request Passed to internal handler
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->renderer === null) {
            return $this->generatePlainTextResponse($request);
        }

        return $this->generateTemplatedResponse($request);
    }

    /**
     * Generates a plain text response indicating the request method and URI.
     */
    private function generatePlainTextResponse(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->responseFactory->createResponse()->withStatus(StatusCodeInterface::STATUS_NOT_FOUND);
        $response->getBody()
            ->write(sprintf(
                'Cannot %s %s',
                $request->getMethod(),
                (string) $request->getUri()
            ));

        return $response;
    }

    /**
     * Generates a response using a template.
     *
     * Template will receive the current request via the "request" variable.
     */
    private function generateTemplatedResponse(ServerRequestInterface $request): ResponseInterface {
        $response = $this->responseFactory->createResponse()->withStatus(StatusCodeInterface::STATUS_NOT_FOUND);
        $response->getBody()->write(
            $this->layoutRenderer->renderTemplate($this->template, $request),
        );

        return $response;
    }
}