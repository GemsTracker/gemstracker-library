<?php

namespace Gems\Model\Transform;

use Zalt\Model\MetaModelInterface;
use Zalt\Model\Transform\ModelTransformerAbstract;

class TrackOrganizationTransformer extends ModelTransformerAbstract
{
    public function transformFilter(MetaModelInterface $model, array $filter): array
    {
        if (isset($filter['organization']) && is_numeric($filter['organization'])) {
            $filter[] = 'gtr_organizations LIKE \'%|' . $filter['organization'] . '|%\'';
            unset($filter['organization']);
        }
        return $filter;
    }
}