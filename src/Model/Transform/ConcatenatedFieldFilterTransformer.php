<?php

namespace Gems\Model\Transform;

use Zalt\Model\MetaModelInterface;
use Zalt\Model\Transform\ModelTransformerAbstract;

class ConcatenatedFieldFilterTransformer extends ModelTransformerAbstract
{
    public function __construct(
        protected readonly string $concatenatedField,
        protected readonly string $separator = '|',
    )
    {}

    public function transformFilter(MetaModelInterface $model, array $filter): array
    {
        if (isset($filter[$this->concatenatedField])) {
            $concatenatedIds = [];
            if (is_numeric($filter[$this->concatenatedField])) {
                $concatenatedIds = [$filter[$this->concatenatedField]];

            }
            if (is_array($filter[$this->concatenatedField])) {
                $concatenatedIds = $filter[$this->concatenatedField];
            }
            if (count($concatenatedIds)) {
                foreach($concatenatedIds as $concatenatedId) {
                    if (is_numeric($concatenatedId)) {
                        $filter[] = $this->concatenatedField . ' LIKE \'%'. $this->separator . $concatenatedId . $this->separator .'%\'';
                    }
                }
            }
            unset($filter[$this->concatenatedField]);
        }

        return $filter;
    }
}