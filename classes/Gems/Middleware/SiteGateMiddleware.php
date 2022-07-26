<?php

namespace Gems\Middleware;

use Gems\Log\Loggers;
use Gems\Site\NotAllowedUrlException;
use Gems\Site\SiteUtil;
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SiteGateMiddleware implements MiddlewareInterface
{
    private SiteUtil $siteUtil;
    private Loggers $loggers;

    private string $logName = 'siteLogger';
    private TemplateRendererInterface $template;

    public function __construct(SiteUtil $siteUtil, Loggers $loggers, TemplateRendererInterface $template)
    {
        $this->siteUtil = $siteUtil;
        $this->loggers = $loggers;
        $this->template = $template;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $this->siteUtil->isRequestFromAllowedUrl($request);
        } catch(NotAllowedUrlException $e) {
            $logger = $this->loggers->getLogger($this->logName);
            $logger->warning(sprintf('Unknown host: %s', $e->getUrl()));

            // For now fall back to 404! 403 might be appropriate as well
            return new HtmlResponse($this->template->render('error::404'), 404);
        }

        return $handler->handle($request);
    }
}