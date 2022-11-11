<?php

declare(strict_types = 1);

namespace Gems\Layout;

use Gems\AuthNew\AuthenticationMiddleware;
use Gems\Middleware\CurrentOrganizationMiddleware;
use Gems\Middleware\LocaleMiddleware;
use Gems\Middleware\MenuMiddleware;
use Gems\User\User;
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
        CurrentOrganizationMiddleware::CURRENT_ORGANIZATION_ATTRIBUTE,
    ];

    public function __construct(protected TemplateRendererInterface $template, private readonly array $config)
    {}

    protected function getAvailableOrganizations(ServerRequestInterface $request): ?array
    {
        /**
         * @var $user User
         */
        $user = $request->getAttribute(AuthenticationMiddleware::CURRENT_USER_ATTRIBUTE);
        //if ($user->hasPrivilege('pr.organization-switch')) {
        if (true) {
            return $user->getAllowedOrganizations();
        }
        return null;
    }

    protected function getDefaultParams(ServerRequestInterface $request): array
    {
        $params = [];

        foreach($this->requestAttributes as $requestAttributeName) {
            $params[$requestAttributeName] = $request->getAttribute($requestAttributeName);
        }

        return $params;
    }

    public function render(LayoutSettings $layoutSettings, ServerRequestInterface $request, array $params = []): string
    {
        $defaultParams = $this->getDefaultParams($request);
        if (!$layoutSettings->showMenu()) {
            $defaultParams[MenuMiddleware::class] = null;
        }
        $params['available_organizations'] = $this->getAvailableOrganizations($request);

        $params['resources'] = $layoutSettings->getResources();

        if ($request->getAttribute(AuthenticationMiddleware::CURRENT_USER_ATTRIBUTE)) {
            array_unshift($params['resources'], 'resource/js/authenticated.js');
        }
        array_unshift($params['resources'], 'resource/js/general.js');

        $params += $defaultParams;

        $params['_config'] = [
            'max_idle_time' => $this->config['session']['max_idle_time'],
            'auth_poll_interval' => $this->config['session']['auth_poll_interval'],
        ];

        return $this->template->render($layoutSettings->getTemplate(), $params);
    }

    public function renderTemplate(string $templateName, ServerRequestInterface $request, array $params = []): string
    {
        $settings = new LayoutSettings();
        $settings->setTemplate($templateName);

        return $this->render($settings, $request, $params);
    }
}