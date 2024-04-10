<?php

namespace Gems\Middleware;

use Gems\Layout\LayoutRenderer;
use Gems\Layout\LayoutSettings;
use Gems\Log\Loggers;
use Gems\Site\NotAllowedUrlException;
use Gems\Site\SiteUtil;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zalt\Base\BaseDir;
use Zalt\Base\TranslatorInterface;
use Zalt\Html\Html;
use Zalt\Message\MezzioSessionMessenger;

class SiteGateMiddleware implements MiddlewareInterface
{
    public const SITE_URL_ATTRIBUTE = 'site';

    protected string $logName = 'siteLogger';

    public function __construct(
        protected readonly LayoutRenderer $layoutRenderer,
        protected readonly LayoutSettings $layoutSettings,
        protected readonly Loggers $loggers,
        protected readonly SiteUtil $siteUtil,
        protected readonly TranslatorInterface $translator,
    )
    { }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request    = BaseDir::withBaseDir($request);
        $blockedUrl = $this->siteUtil->isRequestFromBlockingUrl($request);
        if ($blockedUrl) {
            /**
             * @var MezzioSessionMessenger $statusMessenger
             */
            $statusMessenger =  $request->getAttribute(FlashMessageMiddleware::STATUS_MESSENGER_ATTRIBUTE);
            $statusMessenger->addDanger($this->translator->_('Page blocked by security!'));

//            $data = ['statusMessenger' => $statusMessenger];
            $logger = $this->loggers->getLogger($this->logName);
            $logger->warning(sprintf('Unknown host: %s', $blockedUrl));
            $data['content'] = Html::create('p', $this->translator->_('This page was blocked for security reasons!'));

            return new HtmlResponse($this->layoutRenderer->render($this->layoutSettings, $request, $data), 403);
        }

        $site = $this->siteUtil->getCurrentSite($request);
        $request = $request->withAttribute(self::SITE_URL_ATTRIBUTE, $site);

        return $handler->handle($request);
    }
}