<?php

namespace Gems\Model\Transform;

use MUtil\Model\ModelTransformerAbstract;
use Zalt\Model\MetaModelInterface;

class TrackValidTransformer extends ModelTransformerAbstract
{
    public function transformFilter(MetaModelInterface $model, array $filter)
    {
        if (isset($filter['valid']) && $filter['valid'] == 1) {
            $filter[] = new \Zend_Db_Expr('gtr_date_start < CURRENT_TIMESTAMP');
            $filter[] = new \Zend_Db_Expr('(gtr_date_until IS NULL OR gtr_date_until > CURRENT_TIMESTAMP)');
            unset($filter['valid']);
        }

        return $filter;
    }
}