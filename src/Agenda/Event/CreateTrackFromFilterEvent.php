<?php

declare(strict_types=1);

namespace Gems\Agenda\Event;

use Gems\Agenda\Appointment;
use Gems\Agenda\Filter\TrackFieldFilterCalculationInterface;
use Gems\Tracker\RespondentTrack;
use Symfony\Contracts\EventDispatcher\Event;

class CreateTrackFromFilterEvent extends Event
{
    public function __construct(
        public readonly Appointment $appointment,
        public readonly TrackFieldFilterCalculationInterface $filter,
        public readonly RespondentTrack $respondentTrack,
    )
    {
    }
}