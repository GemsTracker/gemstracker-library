<?php

namespace Gems\Model\Transform;

use Zalt\Model\MetaModelInterface;
use Zalt\Model\Transform\ModelTransformerAbstract;

class RespondentIdTransformer extends ModelTransformerAbstract
{
    public function __construct(
        private readonly int $respondentId,
        private readonly string $respondentIdField
    )
    {}

    public function transformRowBeforeSave(MetaModelInterface $model, array $row): array
    {
        $row[$this->respondentIdField] = $this->respondentId;

        return $row;
    }

}