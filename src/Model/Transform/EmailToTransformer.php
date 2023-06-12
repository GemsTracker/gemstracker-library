<?php

namespace Gems\Model\Transform;

use Zalt\Model\MetaModelInterface;
use Zalt\Model\Transform\ModelTransformerAbstract;

class EmailToTransformer extends ModelTransformerAbstract
{
    protected function getEmailFrom(array $row): string|null
    {
        if ($row['gr2o_email']) {
            return $row['gr2o_email'];
        }
        return null;
    }

    public function transformLoad(MetaModelInterface $model, array $data, $new = false, $isPostData = false)
    {
        foreach($data as $key => $row) {
            $data[$key]['to'] = $this->getEmailFrom($row);
        }

        return $data;
    }
}