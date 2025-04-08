<?php

namespace Gems\Tracker\Token;

use Gems\Legacy\CurrentUserRepository;
use Gems\Menu\RouteHelper;
use Gems\Repository\OrganizationRepository;
use Gems\Tracker\Token;
use Gems\User\User;
use Psr\Http\Message\ServerRequestInterface;
use Zalt\Base\BaseDir;

class TokenHelpers
{
    protected readonly ?User $currentUser;

    public function __construct(
        CurrentUserRepository $currentUserRepository,
        protected readonly OrganizationRepository $organizationRepository,
        protected readonly RouteHelper $routeHelper,
        protected readonly array $config,
    )
    {
        $this->currentUser = $currentUserRepository->getCurrentUser();
    }

    /**
     * @param Token $token
     * @return string The url to return to after LimeSurvey ask/return when nothing else has been specified
     */
    public function getDefaultReturnUrl(Token $token): string
    {
        if ($this->currentUser && $this->currentUser->isActive() && (! $this->currentUser->isLogoutOnSurvey())) {
            $route = 'respondent.show';
        } else {
            $route = 'ask.forward';
        }

        $baseUrl = $token->getOrganization()->getPreferredSiteUrl();
        $baseDir = BaseDir::getBaseDir();
        if ($baseDir && str_ends_with($baseUrl, $baseUrl)) {
            $baseUrl = substr($baseUrl, 0, -strlen($baseDir));
        }

        return $baseUrl . $this->routeHelper->getRouteUrl($route, $token->getMenuUrlParameters());
    }

    /**
     * @param ServerRequestInterface $request
     * @param Token $token
     * @return string The url to return to after LimeSurvey forwarded to ask/return checking for a return parameter or the HTTP_REFERER
     */
    public function getReturnUrl(ServerRequestInterface $request, Token $token): string
    {
        $queryParams = $request->getQueryParams();
        if (isset($queryParams['return'])) {
            $returnUrl = base64_decode($queryParams['return']);
            if ($this->isValidReturnUrl($returnUrl)) {
                return $returnUrl;
            }
        }

        // Check referrer only when logged in!
        if ($this->currentUser && $this->currentUser->isActive()) {
            $serverParams = $request->getServerParams();
            if (isset($serverParams['HTTP_REFERER']) && $this->isValidReturnUrl($serverParams['HTTP_REFERER'])) {
                return $serverParams['HTTP_REFERER'];
            }
        }

        return $this->getDefaultReturnUrl($token);
    }

    public function isValidReturnUrl($url): bool
    {
        // Prevent eternal loop as /ask/return/ the url called by LimeSurvey and is looking for the forward url
        if (str_contains($url, '/ask/return/')) {
            return false;
        }

        if ($this->organizationRepository->isAllowedUrl($url)) {
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