<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Snippets\Vue
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Snippets\Vue;

use Gems\Layout\LayoutSettings;
use Gems\Legacy\CurrentUserRepository;
use Gems\Locale\Locale;
use Mezzio\Helper\UrlHelper;
use Zalt\Base\RequestInfo;
use Zalt\Model\MetaModelInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 * @package    Gems
 * @subpackage Snippets\Vue
 * @since      Class available since version 1.0
 */
class TokenTimelineVueSnippet extends VueSnippetAbstract
{
    protected int $organizationId;

    protected string $patientNr;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        LayoutSettings $layoutSettings,
        Locale $locale,
        UrlHelper $urlHelper,
        array $config,
        protected readonly CurrentUserRepository $currentUserRepository,
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $layoutSettings, $locale, $urlHelper, $config);
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
        $this->currentUserRepository->assertAccessToOrganizationId($this->organizationId);
        return parent::hasHtmlOutput();
    }
}