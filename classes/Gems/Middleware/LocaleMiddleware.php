<?php

namespace Gems\Middleware;

use Gems\Locale\Locale;
use Gems\Site\SiteUrl;
use Gems\User\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LocaleMiddleware implements MiddlewareInterface
{
    protected array $config = [];

    private Locale $locale;

    public function __construct(Locale $locale, array $config)
    {
        // TODO: Locale should not be a serviceas it is stateful
        if (isset($config['locale'])) {
            $this->config = $config['locale'];
        }
        $this->locale = $locale;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $language = $this->getLanguage($request);
        if ($language !== null) {
            $this->locale->setCurrentLanguage($language);
        }

        $response = $handler->handle($request);
        if ($language === null) {
            return $response;
        }


        return $response->withAddedHeader('Content-Language', $language);
    }

    protected function getLanguage(ServerRequestInterface $request): ?string
    {
        if (!isset($this->config['availableLocales'])) {
            return null;
        }
        // Is the language specifically asked for in the headers?
        $headerLanguage = $request->getHeaderLine('Accept-Language');
        if (!empty($headerLanguage) && in_array($headerLanguage, $this->config['availableLocales'])) {
            return $headerLanguage;
        }

        // Default Language from CurrentUser
        $currentUser = $request->getAttribute('current_user');
        if ($currentUser instanceof User) {
            $language = $currentUser->getLocale();
            if ($language !== null && in_array($language, $this->config['availableLocales'])) {
                return $language;
            }
        }

        // Default language from site
        $site = $request->getAttribute(SiteGateMiddleware::SITE_URL_ATTRIBUTE);
        if ($site instanceof SiteUrl) {
            $language = $site->getLang();
            if ($language !== null && in_array($language, $this->config['availableLocales'])) {
                return $language;
            }
        }

        // Default language from config
        if (isset($this->config['default']) && in_array($this->config['default'], $this->config['availableLocales'])) {
            return $this->config['default'];
        }

        return null;
    }
}
