<?php

namespace Gems\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LegacyModelMiddleware implements MiddlewareInterface
{
    public function __construct(
        protected readonly array $config,
    )
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (isset($this->config['model']['bridges'])) {
            foreach ($this->config['model']['bridges'] as $name => $className) {
                \MUtil\Model::setDefaultBridge($name, $className);
            }
        }

        return $handler->handle($request);
    }
}