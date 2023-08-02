<?php

namespace Gems\Agenda\Event;

use Gems\Agenda\Agenda;
use Gems\Agenda\Appointment;
use Symfony\Contracts\EventDispatcher\Event;

class AppointmentEvent extends Event
{
    public function __construct(
        protected readonly Appointment $appointment,
        protected readonly Agenda $agenda,
    )
    {}

    /**
     * @return Agenda
     */
    public function getAgenda(): Agenda
    {
        return $this->agenda;
    }

    /**
     * @return Appointment
     */
    public function getAppointment(): Appointment
    {
        return $this->appointment;
    }
}