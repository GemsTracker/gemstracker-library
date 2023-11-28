<?php

namespace Gems\Model\Transform;

use Zalt\Model\MetaModelInterface;
use Zalt\Model\Transform\ModelTransformerAbstract;

class AddValuesTransformer extends ModelTransformerAbstract
{
    public function __construct(protected readonly array $values)
    {}

    public function transformLoad(MetaModelInterface $model, array $data, $new = false, $isPostData = false): array
    {
        foreach($data as $key=>$row) {
            foreach($this->values as $fieldName => $value) {
                $data[$key][$fieldName] = $value;
            }
        }
        return $data;
    }
}