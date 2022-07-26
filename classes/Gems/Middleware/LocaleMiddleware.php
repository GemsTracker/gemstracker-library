<?php

namespace Gems\Middleware;

use Gems\Locale\Locale;
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

        // Default language from site

        // Default language from config
        if (isset($this->config['default']) && in_array($this->config['default'], $this->config['availableLocales'])) {
            return $this->config['default'];
        }

        return null;
    }
}
