<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Snippets\Vue
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Snippets\Vue;

use Gems\Config\Menu;
use Gems\Layout\LayoutSettings;
use Gems\Legacy\CurrentUserRepository;
use Gems\Locale\Locale;
use Gems\Menu\MenuSnippetHelper;
use Gems\User\User;
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
    protected readonly User $currentUser;

    protected int $organizationId;

    protected string $patientNr;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        LayoutSettings $layoutSettings,
        Locale $locale,
        MenuSnippetHelper $menuSnippetHelper,
        UrlHelper $urlHelper,
        array $config,
        CurrentUserRepository $currentUserRepository,
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $layoutSettings, $locale, $menuSnippetHelper, $urlHelper, $config);

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