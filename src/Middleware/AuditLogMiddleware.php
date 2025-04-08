<?php

namespace Gems\Middleware;

use Gems\Audit\AuditLog;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuditLogMiddleware implements MiddlewareInterface
{
    public const AUDIT_LOG_ID = 'auditLogId';

    public function __construct(
        protected AuditLog $auditLog,
    )
    {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $logId = $this->auditLog->registerRequest($request);

        if ($logId) {
            $request->withAttribute(static::AUDIT_LOG_ID, $logId);
        }

        $response = $handler->handle($request);
        // Check if log should be updated

        return $response;
    }
}