<?php

namespace Gems\Model\Transform;

use Zalt\Model\MetaModelInterface;
use Zalt\Model\Transform\ModelTransformerAbstract;

class TrackValidTransformer extends ModelTransformerAbstract
{
    public function transformFilter(MetaModelInterface $model, array $filter): array
    {
        if (isset($filter['valid']) && $filter['valid'] == 1) {
            $filter[] = new \Zend_Db_Expr('gtr_date_start < CURRENT_TIMESTAMP');
            $filter[] = new \Zend_Db_Expr('(gtr_date_until IS NULL OR gtr_date_until > CURRENT_TIMESTAMP)');
            unset($filter['valid']);
        }

        return $filter;
    }
}