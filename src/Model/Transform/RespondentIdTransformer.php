<?php

namespace Gems\Model\Transform;

use Zalt\Model\MetaModelInterface;
use Zalt\Model\Transform\ModelTransformerAbstract;

class RespondentIdTransformer extends FixedValueTransformer
{
    public function __construct(
        int $respondentId,
        string $respondentIdField
    )
    {
        parent::__construct([$respondentIdField => $respondentId]);
    }
}