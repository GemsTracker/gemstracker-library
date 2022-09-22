<?php

declare(strict_types = 1);

namespace Gems\Layout;

use Gems\AuthNew\AuthenticationMiddleware;
use Gems\Middleware\LocaleMiddleware;
use Gems\Middleware\MenuMiddleware;
use Mezzio\Flash\FlashMessageMiddleware;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ServerRequestInterface;

class LayoutRenderer
{
    protected array $requestAttributes = [
        MenuMiddleware::MENU_ATTRIBUTE,
        FlashMessageMiddleware::FLASH_ATTRIBUTE,
        LocaleMiddleware::LOCALE_ATTRIBUTE,
        AuthenticationMiddleware::CURRENT_IDENTITY_ATTRIBUTE,
    ];

    public function __construct(protected TemplateRendererInterface $template)
    {}

    public function render(string $name, ServerRequestInterface $request, $params = []): string
    {
        $params += $this->getDefaultParams($request);

        return $this->template->render($name, $params);
    }

    protected function getDefaultParams(ServerRequestInterface $request)
    {
        $params = [];

        foreach($this->requestAttributes as $requestAttributeName) {
            $params[$requestAttributeName] = $request->getAttribute($requestAttributeName);
        }

        return $params;
    }

}