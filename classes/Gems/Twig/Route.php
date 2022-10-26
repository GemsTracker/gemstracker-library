<?php

namespace Gems\Twig;

use Mezzio\Helper\UrlHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Route extends AbstractExtension
{
    public function __construct(private readonly UrlHelper $urlHelper)
    {
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('route', [$this, 'renderRoute']),
        ];
    }

    public function renderRoute(
        string $routeName,
        array $routeParams = [],
        array $queryParams = [],
        ?string $fragmentIdentifier = null
    ): string {
        return $this->urlHelper->generate($routeName, $routeParams, $queryParams, $fragmentIdentifier);
    }
}
