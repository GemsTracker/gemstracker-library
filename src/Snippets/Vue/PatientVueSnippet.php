<?php

namespace Gems\Snippets\Vue;

use Gems\Layout\LayoutSettings;
use Gems\Legacy\CurrentUserRepository;
use Gems\Locale\Locale;
use Gems\User\User;
use Mezzio\Helper\UrlHelper;
use Zalt\Base\RequestInfo;
use Zalt\Model\MetaModelInterface;
use Zalt\SnippetsLoader\SnippetOptions;

class PatientVueSnippet extends VueSnippetAbstract
{
    protected readonly User $currentUser;

    protected int $organizationId;

    protected string $patientNr;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        LayoutSettings $layoutSettings,
        Locale $locale,
        UrlHelper $urlHelper,
        array $config,
        CurrentUserRepository $currentUserRepository,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $layoutSettings, $locale, $urlHelper, $config);

        $this->currentUser = $currentUserRepository->getCurrentUser();
    }

    protected function getAttributes(): array
    {
        $attributes = parent::getAttributes();
        $attributes['patient-nr'] = $this->patientNr;
        $attributes[':organization-id'] = $this->organizationId;

        return $attributes;
    }

    public function hasHtmlOutput(): bool
    {
        $attributes = $this->requestInfo->getRequestMatchedParams();
        if (!isset($attributes[MetaModelInterface::REQUEST_ID1], $attributes[MetaModelInterface::REQUEST_ID2])) {
            return false;
        }
        $this->patientNr = (string) $attributes[MetaModelInterface::REQUEST_ID1];
        $this->organizationId = (int) $attributes[MetaModelInterface::REQUEST_ID2];
        $this->currentUser->assertAccessToOrganizationId($this->organizationId, null);
        return parent::hasHtmlOutput();
    }
}