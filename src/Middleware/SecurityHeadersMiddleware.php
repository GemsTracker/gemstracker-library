<?php

declare(strict_types=1);

namespace Gems\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SecurityHeadersMiddleware implements MiddlewareInterface
{
    protected array $config = [];

    public function __construct(array $config)
    {
        if (isset($config['security'], $config['security']['headers'])) {
            $this->config = $config['security']['headers'];
        }
    }

    protected function applyHeadersToResponse(ResponseInterface $response, array $headers): ResponseInterface
    {
        foreach($headers as $name => $value) {
            if ($value === null) {
                continue;
            }
            $response = $response->withHeader($name, $value);
        }
        return $response;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response  = $handler->handle($request);

        foreach($this->config as $responseClass => $headers) {
            if ($response instanceof $responseClass) {
                return $this->applyHeadersToResponse($response, $headers);
            }
        }
        if (isset($this->config['default']) && $this->config['default']) {
            return $this->applyHeadersToResponse($response, $this->config['default']);
        }

        return $response;
    }
}
