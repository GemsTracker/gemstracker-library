<?php

namespace Gems\Model\Type;

use MUtil\Model\Type\JsonData;

class AppointmentInfoType extends JsonData
{
    public function loadValue(
        mixed $value,
        bool $isNew = false,
        ?string $name = null,
        array $context = [],
        bool $isPost = false
    ): ?array {
        $result = parent::loadValue($value, $isNew, $name, $context, $isPost);

    }
}