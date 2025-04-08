<?php

declare(strict_types=1);

namespace Gems\Model\Transform;

use Zalt\Model\MetaModelInterface;
use Zalt\Model\Transform\ModelTransformerAbstract;

class FixedValueTransformer extends ModelTransformerAbstract
{
    public function __construct(
        private readonly array $fixedFieldValues,
    )
    {
    }

    public function transformLoad(MetaModelInterface $model, array $data, $new = false, $isPostData = false): array
    {
        foreach($data as $key=>$row) {
            foreach($this->fixedFieldValues as $column => $value) {
                $data[$key][$column] = $value;
            }
        }
        return $data;
    }

    public function transformRowBeforeSave(MetaModelInterface $model, array $row): array
    {
        foreach($this->fixedFieldValues as $column => $value) {
            $row[$column] = $value;
        }
        return $row;
    }
}