<?php

namespace Gems\Tracker\Field;

use Gems\Agenda\Agenda;
use Gems\Repository\AppointmentInfoTypesRepository;
use Gems\Util\Translated;
use Zalt\Base\TranslatorInterface;


class AppointmentInfoField extends FieldAbstract
{

    public function __construct(
        int $trackId,
        string $fieldKey,
        array $fieldDefinition,
        TranslatorInterface $translator,
        Translated $translatedUtil,
        protected readonly Agenda $agenda,
        protected readonly AppointmentInfoTypesRepository $appointmentInfoTypesRepository,
    ) {
        parent::__construct($trackId, $fieldKey, $fieldDefinition, $translator, $translatedUtil);
    }

    protected function addModelSettings(array &$settings): void
    {
    }

    public function calculateFieldValue(mixed $currentValue, array $fieldData, array $trackData): mixed
    {
        $calcUsing = $this->getCalculationFields($fieldData);

        if ($calcUsing) {
            // Get the used fields with values
            foreach (array_filter($calcUsing) as $value) {
                $appointment = $this->agenda->getAppointment($value);

                if ($appointment->exists) {

                    $info = $appointment->getInfo();

                    $fieldName = $this->fieldDefinition['gtf_field_values'];
                    if ($fieldName && array_key_exists($fieldName, $info)) {
                        $options = $this->appointmentInfoTypesRepository->getValueOptionsForType($fieldName);
                        if ($options && isset($options[$info[$fieldName]])) {
                            return $options[$info[$fieldName]];
                        }

                        return $info[$fieldName];
                    }
                    return null;
                }
            }
        }
        return $currentValue;
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}