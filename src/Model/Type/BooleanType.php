<?php

namespace Gems\Model\Type;

use Zalt\Model\MetaModelInterface;
use Zalt\Model\Type\AbstractModelType;

class BooleanType extends AbstractModelType
{
    public function apply(MetaModelInterface $metaModel, string $name)
    {
        $metaModel->setOnLoad($name, [$this, 'castToBoolean']);
    }

    public function castToBoolean(mixed $value): bool
    {
        return (bool) $value;
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