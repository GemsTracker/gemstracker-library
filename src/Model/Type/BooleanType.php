<?php

namespace Gems\Model\Type;

use Zalt\Model\MetaModelInterface;
use Zalt\Model\Type\AbstractModelType;
use Zalt\Model\Type\ModelTypeInterface;
use Zalt\Model\Type\YesNoType;

class BooleanType extends AbstractModelType
{
    public function apply(MetaModelInterface $metaModel, string $name)
    {
        $metaModel->setOnLoad($name, 'boolval');
    }

    public function getBaseType(): int
    {
        return MetaModelInterface::TYPE_NUMERIC;
    }

    public function getSettings(): array
    {
        return [
            'elementClass' => 'CheckBox',
        ];
    }
}