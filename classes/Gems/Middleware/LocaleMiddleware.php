<?php

namespace Gems\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LocaleMiddleware implements MiddlewareInterface
{
    protected array $config = [];

    public function __construct(array $config)
    {
        if (isset($config['locale'])) {
            $this->config = $config['locale'];
        }
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $language = $this->getLanguage($request);

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
        if (isset($config['default']) && in_array($config['default'], $this->config['availableLocales'])) {
            return $config['default'];
        }

        return null;
    }
}
