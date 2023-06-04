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

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response  = $handler->handle($request);

        foreach($this->config as $responseClass => $headers) {
            if ($response instanceof $responseClass) {
                foreach($headers as $name => $value) {
                    $response = $response->withHeader($name, $value);
                }
            }
        }

        return $response;
    }
}
