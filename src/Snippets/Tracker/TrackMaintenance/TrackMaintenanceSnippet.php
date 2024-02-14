<?php

declare(strict_types=1);


namespace Gems\Snippets\Tracker\TrackMaintenance;

use Gems\Legacy\CurrentUserRepository;
use Gems\Menu\MenuSnippetHelper;
use Gems\Repository\OrganizationRepository;
use Gems\Snippets\ModelTableSnippet;
use Laminas\Db\Sql\Predicate\Like;
use Laminas\Db\Sql\Predicate\Predicate;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @since      Class available since version 2.x
 */
class TrackMaintenanceSnippet extends ModelTableSnippet
{
    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        MenuSnippetHelper $menuHelper,
        TranslatorInterface $translate,
        protected readonly CurrentUserRepository $currentUserRepository,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $menuHelper, $translate);
    }

    public function getFilter(MetaModelInterface $metaModel): array
    {
        $filter = parent::getFilter($metaModel);

        $allowedOrganizationIds = $this->currentUserRepository->getAllowedOrganizationIds();
        $wheres = [];
        foreach ($allowedOrganizationIds as $organizationId) {
            $wheres[] = new Like('gtr_organizations', '%|' . $organizationId . '|%');
        }
        if ($wheres) {
            $where = new Predicate($wheres, Predicate::COMBINED_BY_OR);
        } else {
            $where = OrganizationRepository::SYSTEM_NO_ORG;
        }

        $filter['gtr_organizations'] = $where;

        return $filter;
    }
}