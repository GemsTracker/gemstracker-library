<?php

namespace GemsTest\Tracker\Field;

use Gems\Agenda\Agenda;
use Gems\Agenda\LaminasAppointmentSelect;
use Gems\Menu\RouteHelper;
use Gems\Tracker\Field\AppointmentField;
use Gems\Util\Translated;
use Zalt\Base\TranslatorInterface;

class AppointmentFieldWithClonedSelect extends AppointmentField
{
    public function __construct(
        int $trackId,
        string $fieldKey,
        array $fieldDefinition,
        TranslatorInterface $translator,
        Translated $translatedUtil,
        Agenda $agenda,
        RouteHelper $routeHelper,
        private readonly LaminasAppointmentSelect $appointmentSelect,
    ) {
        parent::__construct($trackId, $fieldKey, $fieldDefinition, $translator, $translatedUtil, $agenda, $routeHelper);
    }

    protected function getCancelledSelect(
        LaminasAppointmentSelect $select,
        mixed $currentValue
    ): LaminasAppointmentSelect {
        return $this->appointmentSelect;
    }
}