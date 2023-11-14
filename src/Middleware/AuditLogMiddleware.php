<?php

namespace Gems\Middleware;

use Gems\Audit\AuditLog;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuditLogMiddleware implements MiddlewareInterface
{
    public const AUDIT_LOG_DATA = 'auditLogData';
    public const AUDIT_LOG_MESSAGES = 'auditLogMessage';
    public const RESPONDENT_ID_ATTRIBUTE = 'respondentId';


    public function __construct(
        protected AuditLog $auditLog,
    )
    {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $log = $this->auditLog->registerRequest($request);

        $response = $handler->handle($request);
        // Check if log should be updated

        return $response;
    }
}