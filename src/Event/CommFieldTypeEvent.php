<?php

namespace Gems\Event;

use Symfony\Contracts\EventDispatcher\Event;

class CommFieldTypeEvent extends Event
{
    public function __construct(
        protected array $fieldTypes = [],
    )
    {
    }

    public function addFieldType(string $fieldType): void
    {
        $this->fieldTypes[] = $fieldType;
    }

    public function getFieldTypes(): array
    {
        return $this->fieldTypes;
    }

    public function setFieldTypes(array $fieldTypes): void
    {
        $this->fieldTypes = $fieldTypes;
    }
}