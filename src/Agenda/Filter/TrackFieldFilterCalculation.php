<?php

namespace Gems\Agenda\Filter;

use Gems\Tracker\Engine\FieldsDefinition;
use Gems\Tracker\Model\FieldMaintenanceModel;

class TrackFieldFilterCalculation implements TrackFieldFilterCalculationInterface
{

    public function __construct(
        protected readonly int $appointmentFieldId,
        protected readonly int $trackId,
        protected readonly AppointmentFilterInterface $appointmentFilter,
        protected readonly int $creatorType,
        protected readonly int $createWaitDays,


    )
    {}

    /**
     * @return AppointmentFilterInterface
     */
    public function getAppointmentFilter(): AppointmentFilterInterface
    {
        return $this->appointmentFilter;
    }

    public function getCreatorType(): int
    {
        return $this->creatorType;
    }

    public function getFieldId(): string
    {
        return FieldsDefinition::makeKey(
            FieldMaintenanceModel::APPOINTMENTS_NAME,
            $this->appointmentFieldId,
        );
    }

    public function getTrackId(): int
    {
        return $this->trackId;
    }

    public function getWaitDays(): int
    {
        return $this->createWaitDays;
    }

    public function isCreator(): bool
    {
        return (bool)$this->getCreatorType();
    }
}