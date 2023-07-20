<?php

namespace Gems\Dev\Clockwork\Middleware;

use Clockwork\Clockwork;
use Clockwork\DataSource\PsrMessageDataSource;
use Clockwork\Helpers\ServerTiming;
use Mezzio\Helper\UrlHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ClockworkMiddleware implements MiddlewareInterface
{
    protected float $startTime;

    public function __construct(
        protected readonly Clockwork $clockwork,
        protected readonly UrlHelper $urlHelper,
    )
    {
        $this->startTime = microtime(true);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        return $this->logRequest($request, $response);
    }

    protected function logRequest(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->clockwork->timeline()->finalize($this->startTime);
        $this->clockwork->addDataSource(new PsrMessageDataSource($request, $response));

        $this->clockwork->resolveRequest();
        $this->clockwork->storeRequest();

        $clockworkRequest = $this->clockwork->request();

        $response = $response
            ->withHeader('X-Clockwork-Id', $clockworkRequest->id)
            ->withHeader('X-Clockwork-Version', Clockwork::VERSION);

        if ($basePath = $this->urlHelper->getBasePath()) {
            $response = $response->withHeader('X-Clockwork-Path', "$basePath/__clockwork/");
        }

        return $response->withHeader('Server-Timing', ServerTiming::fromRequest($clockworkRequest)->value());
    }
}