<?php

namespace Gems\Model\Transform;

use Gems\Repository\OrganizationRepository;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\Transform\ModelTransformerAbstract;

class OrganizationAccessTransformer extends ModelTransformerAbstract
{
    public function __construct(
        protected readonly OrganizationRepository $organizationRepository,
    ) {
    }

    public function transformFilter(MetaModelInterface $model, array $filter): array
    {
        // If there is no organization filter at all, we can't filter on multiple organizations.
        if (!isset($filter['gr2t_id_organization'])) {
            return $filter;
        }
        // If we're already filtering on multiple organizations, don't change that.
        if (is_array($filter['gr2t_id_organization'])) {
            return $filter;
        }
        $filter['gr2t_id_organization'] = array_keys($this->organizationRepository->getAllowedOrganizationsFor($filter['gr2t_id_organization']));
        return $filter;
    }
}
