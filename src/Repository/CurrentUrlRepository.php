<?php

namespace Gems\Repository;

use Mezzio\Helper\UrlHelper;
use Zalt\Base\RequestUtil;

class CurrentUrlRepository
{
    public function __construct(
        private readonly OrganizationRepository $organizationRepository,
        private readonly UrlHelper $urlHelper,
    )
    {
    }

    public function getCurrentUrl(): string|null
    {
        return $this->organizationRepository->getAllowedUrl(RequestUtil::getCurrentUrl($this->urlHelper->getRequest()));
    }
}