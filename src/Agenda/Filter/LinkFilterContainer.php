<?php

namespace Pulse\Agenda\Filter\LinkFilterContainer;
use Gems\Agenda\Filter\AppointmentFilterInterface;

class LinkFilterContainer
{
    public function __construct(
        protected readonly AppointmentFilterInterface $appointmentFilter,
        protected readonly int $linkFilterId,
        protected readonly string|null $keyField,
        protected readonly mixed $value,
    )
    {
    }

    /**
     * @return AppointmentFilterInterface
     */
    public function getAppointmentFilter(): AppointmentFilterInterface
    {
        return $this->appointmentFilter;
    }

    /**
     * @return string
     */
    public function getKeyField(): string|null
    {
        return $this->keyField;
    }

    /**
     * @return int
     */
    public function getLinkFilterId(): int
    {
        return $this->linkFilterId;
    }

    /**
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->value;
    }
}