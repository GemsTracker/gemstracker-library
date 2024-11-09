<?php

namespace Gems\Middleware;

use Gems\Layout\LayoutRenderer;
use Gems\Layout\LayoutSettings;
use Gems\Log\Loggers;
use Gems\Repository\OrganizationRepository;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zalt\Base\BaseDir;
use Zalt\Base\RequestUtil;
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
        protected readonly OrganizationRepository $organizationRepository,
        protected readonly TranslatorInterface $translator,
    )
    { }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = BaseDir::withBaseDir($request);
        $url     = RequestUtil::getCurrentSite($request);
        $site    = $this->organizationRepository->getAllowedUrl($url);
        if (! $site) {
            /**
             * @var MezzioSessionMessenger $statusMessenger
             */
            $statusMessenger =  $request->getAttribute(FlashMessageMiddleware::STATUS_MESSENGER_ATTRIBUTE);
            $statusMessenger->addDanger($this->translator->_('Page blocked by security!'));

//            $data = ['statusMessenger' => $statusMessenger];
            $logger = $this->loggers->getLogger($this->logName);
            $logger->warning(sprintf('Unknown host: %s', $url));
            $data['content'] = Html::create('p', $this->translator->_('This page was blocked for security reasons!'));

            return new HtmlResponse($this->layoutRenderer->render($this->layoutSettings, $request, $data), 403);
        }

        $request = $request->withAttribute(self::SITE_URL_ATTRIBUTE, $site);

        return $handler->handle($request);
    }
}