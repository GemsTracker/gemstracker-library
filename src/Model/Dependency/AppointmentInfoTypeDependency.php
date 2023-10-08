<?php

namespace Gems\Model\Dependency;

use Gems\Repository\AppointmentInfoTypesRepository;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Dependency\DependencyAbstract;

class AppointmentInfoTypeDependency extends DependencyAbstract
{
    protected array $_dependentOn = ['gai_type'];

    protected array $_defaultEffects = [
        'gai_field_key',
        'gai_field_value',
    ];

    public function __construct(
        TranslatorInterface $translate,
        protected AppointmentInfoTypesRepository $appointmentInfoTypesRepository,
    )
    {
        parent::__construct($translate);
    }


    public function getChanges(array $context, bool $new = false): array
    {
        if ($context['gai_type'] === null) {
            return [];
        }

        $keyName = $this->appointmentInfoTypesRepository->getKeyNameForType($context['gai_type']);
        $valueOptions = $this->appointmentInfoTypesRepository->getValueOptionsForType($context['gai_type']);

        $changes = [];

        if ($keyName) {
            $changes['gai_field_key'] = [
                'elementClass' => 'Exhibitor',
                'value' => $keyName,
            ];
        }
        if ($valueOptions) {
            $changes['gai_field_value'] = [
                'multiOptions' => $valueOptions,
            ];
        }
        return $changes;
    }
}