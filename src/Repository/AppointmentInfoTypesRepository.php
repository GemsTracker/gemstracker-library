<?php

namespace Gems\Repository;

use Zalt\Base\TranslatorInterface;

class AppointmentInfoTypesRepository
{
    public function __construct(
        protected readonly TranslatorInterface $translator)
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