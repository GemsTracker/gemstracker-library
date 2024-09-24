<?php

namespace Gems\Tracker\Token;

use Gems\Menu\RouteHelper;
use Gems\Site\SiteUtil;
use Gems\Tracker\Token;
use MUtil\Model;
use Psr\Http\Message\ServerRequestInterface;
use Zalt\Base\BaseDir;

class TokenHelpers
{
    public function __construct(
        protected RouteHelper $routeHelper,
        protected SiteUtil $siteUtil,
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

        $baseUrl = $token->getOrganization()->getPreferredSiteUrl();
        $baseDir = BaseDir::getBaseDir();
        if ($baseDir && str_ends_with($baseUrl, $baseUrl)) {
            $baseUrl = substr($baseUrl, 0, -strlen($baseDir));
        }

        return $baseUrl . $this->routeHelper->getRouteUrl('ask.forward', [Model::REQUEST_ID => $token->getTokenId()]);
    }

    public function isValidReturnUrl($url): bool
    {
        if ($this->siteUtil->isAllowedUrl($url)) {
            return true;
        }

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