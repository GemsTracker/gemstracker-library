<?php

namespace Gems\Model\Transform;

use MUtil\Model\ModelTransformerAbstract;
use Zalt\Model\MetaModelInterface;

class TrackOrganizationTransformer extends ModelTransformerAbstract
{
    public function transformFilter(MetaModelInterface $model, array $filter)
    {
        if (isset($filter['organization']) && is_numeric($filter['organization'])) {
            $filter[] = 'gtr_organizations LIKE \'%|' . $filter['organization'] . '|%\'';
            unset($filter['organization']);
        }
        return $filter;
    }
}