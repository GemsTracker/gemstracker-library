<?php

namespace Gems\Repository;

use MUtil\Translate\Translator;

class AppointmentInfoTypesRepository
{
    public function __construct(protected readonly Translator $translator)
    {}

    public function getInfoTypeOptions(): array
    {
        return [
            'manual' => $this->translator->_('Manual'),
        ];
    }

    public function getKeyNameForType(string $type): string|null
    {
        return match($type) {
            default => null,
        };
    }

    public function getValueOptionsForType(string $type): array|null
    {
        return match($type) {
            default => null,
        };
    }

}