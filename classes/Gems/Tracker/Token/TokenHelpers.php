<?php

namespace Gems\Tracker\Token;

use Gems\MenuNew\RouteHelper;
use Gems\Tracker\Token;
use MUtil\Model;
use Psr\Http\Message\ServerRequestInterface;

class TokenHelpers
{
    public function __construct(
        protected RouteHelper $routeHelper,
        protected array $config,
    )
    {}
    public function getReturnUrl(ServerRequestInterface $request, Token $token): string
    {
        $queryParams = $request->getQueryParams();
        if (isset($queryParams['return'])) {
            $returnUrl = base64_decode($queryParams['return']);
            if ($this->isValidReturnUrl($returnUrl)) {
                return $returnUrl;
            }
        }

        $serverParams = $request->getServerParams();
        if (isset($serverParams['HTTP_REFERER'])) {
            return $serverParams['HTTP_REFERER'];
        }

        $baseUrl = $token->getOrganization()->getPreferredSiteUrl();

        return $baseUrl . $this->routeHelper->getRouteUrl('ask.forward', [Model::REQUEST_ID => $token->getTokenId()]);
    }

    public function isValidReturnUrl($url): bool
    {
        if (isset($this->config['survey']['ask']['allowedReturnUrls']) && filter_var($url, FILTER_VALIDATE_URL)) {
            foreach($this->config['survey']['ask']['allowedReturnUrls'] as $allowedReturnUrl) {
                if (str_starts_with($url, $allowedReturnUrl)) {
                    return true;
                }
            }
        }
        return false;
    }
}