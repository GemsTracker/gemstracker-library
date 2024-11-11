<?php

namespace Gems\Handlers;

use Gems\Middleware\FlashMessageMiddleware;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Helper\UrlHelper;
use MUtil\Legacy\RequestHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zalt\Base\TranslatorInterface;

class LegacyAskRedirectHandler implements RequestHandlerInterface
{
    public function __construct(
        private UrlHelper $urlHelper,
        private TranslatorInterface $translator,
    )
    {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');
        $requestHelper = new RequestHelper($request);
        $options = $requestHelper->getRouteOptions();
        $action = $options['action'] ?? null;
        if ($action) {
            $url = $this->urlHelper->generate('ask.'.$action, ['id' => $id]);
            if ($url) {
                return new RedirectResponse($url);
            }
        }

        $messenger = $request->getAttribute(FlashMessageMiddleware::STATUS_MESSENGER_ATTRIBUTE);
        $messenger->addMessage(sprintf(
            $this->translator->_('The token %s does not exist (any more).'),
            strtoupper($id)
        ));

        $url = $this->urlHelper->generate('ask.index');
        return new RedirectResponse($url);
    }
}