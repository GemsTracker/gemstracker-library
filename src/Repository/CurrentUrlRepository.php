<?php

namespace Gems\Repository;

use Gems\Site\SiteUtil;
use Mezzio\Helper\UrlHelper;

class CurrentUrlRepository
{
    public function __construct(
        private readonly SiteUtil $siteUtil,
        private readonly UrlHelper $urlHelper,
    )
    {
    }

    public function getCurrentUrl(): string|null
    {
        $site = $this->siteUtil->getCurrentSite($this->urlHelper->getRequest());
        if ($site) {
            return $site->getUrl();
        }
        return null;
    }
}