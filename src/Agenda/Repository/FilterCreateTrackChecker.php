<?php

namespace Gems\Agenda\Repository;

use Gems\Agenda\Agenda;
use Gems\Agenda\Appointment;
use Gems\Agenda\Filter\TrackFieldFilterCalculationInterface;
use Gems\Agenda\FilterTracer;
use Gems\Tracker\RespondentTrack;
use Zalt\Base\TranslatorInterface;

class FilterCreateTrackChecker
{
    public function __construct(
        protected readonly TranslatorInterface $translator,
    )
    {}

    /**
     * Has the track ended <wait days> ago?
     */
    public function createAfterWaitDays(
        Agenda $agenda,
        Appointment $appointment,
        TrackFieldFilterCalculationInterface $filter,
        RespondentTrack $respTrack,
        FilterTracer|null $filterTracer = null
    ): bool
    {
        $createTrack = true;
        $curr        = $appointment->getAdmissionTime();
        $end         = $respTrack->getEndDate();
        $wait        = $filter->getWaitDays();

        $diff = null;
        if ($curr && $end) {
            $diff = $curr->diff($end);
        }

        if ((! $end) || ($diff && $diff->days <= $wait)) {
            $createTrack = false;
            if ($filterTracer) {
                if (! $end) {
                    $filterTracer->setSkipCreationMessage(
                        $this->translator->_('track without an end date')
                    );
                } else {
                    $filterTracer->setSkipCreationMessage(sprintf(
                        $this->translator->_('%d days since previous end date, %d required'),
                        $diff->days,
                        $wait
                    ));
                }
            }
        }
        if ($createTrack) {
            // Test to see whether this track has already been created by this filter
            $fieldId = $filter->getFieldId();
            $data    = $respTrack->getFieldData();
            if (isset($data[$fieldId]) && ($data[$fieldId] == $appointment->getId())) {
                $createTrack = false;
                if ($filterTracer) {
                    $filterTracer->setSkipCreationMessage(
                        $this->translator->_('track has already been created')
                    );
                }
            }
        }

        return $createTrack;
    }

    /**
     * Always report the track should be created
     */
    public function createAlways(
        Agenda $agenda,
        Appointment $appointment,
        TrackFieldFilterCalculationInterface $filter,
        RespondentTrack $respTrack,
        FilterTracer|null $filterTracer = null
    ): bool
    {
        $createTrack = $this->createAfterWaitDays($appointment, $filter, $respTrack, $filterTracer);

        if ($createTrack) {
            $createTrack = $this->createWhenNotInThisTrack($appointment, $filter, $respTrack, $filterTracer);
        }

        return $createTrack;
    }

    /**
     * Always report the track should be created
     */
    public function createAlwaysNoEndDate(
        Agenda $agenda,
        Appointment $appointment,
        TrackFieldFilterCalculationInterface $filter,
        RespondentTrack $respTrack,
        FilterTracer|null $filterTracer = null
    ): bool
    {
        return $this->createWhenNotInThisTrack($agenda, $appointment, $filter, $respTrack, $filterTracer);
    }

    /**
     * Always report the track should be created
     */
    public function createFromStart(
        Agenda $agenda,
        Appointment $appointment,
        TrackFieldFilterCalculationInterface $filter,
        RespondentTrack $respTrack,
        FilterTracer|null $filterTracer = null
    ): bool
    {
        $createTrack = true;
        $curr        = $appointment->getAdmissionTime();
        $start       = $respTrack->getStartDate();
        $wait        = $filter->getWaitDays();

        $diff = null;
        if ($curr && $start) {
            $diff = $curr->diff($start);
        }

        if ((! $start) || ($diff && $diff->days <= $wait)) {
            $createTrack = false;
            if ($filterTracer) {
                if (! $start) {
                    $filterTracer->setSkipCreationMessage(
                        $this->translator->_('track without a startdate')
                    );
                } else {
                    $filterTracer->setSkipCreationMessage(sprintf(
                        $this->translator->_('%d days since previous startdate, %d required'),
                        $diff->days,
                        $wait
                    ));
                }
            }
        }
        if ($createTrack) {
            // Test to see whether this track has already been created by this filter
            $fieldId = $filter->getFieldId();
            $data    = $respTrack->getFieldData();
            if (isset($data[$fieldId]) && ($data[$fieldId] == $appointment->getId())) {
                $createTrack = false;
                if ($filterTracer) {
                    $filterTracer->setSkipCreationMessage(
                        $this->translator->_('track has already been created')
                    );
                }
            }
        }

        return $createTrack;
    }

    public function createFromCurrentField(
        Agenda $agenda,
        Appointment $appointment,
        TrackFieldFilterCalculationInterface $filter,
        RespondentTrack $respTrack,
        FilterTracer|null $filterTracer = null
    ): bool
    {
        $curr        = $appointment->getAdmissionTime();
        $wait        = $filter->getWaitDays();
        $fieldId = $filter->getFieldId();
        $fieldData = $respTrack->getFieldData();

        if (!$curr) {
            // Do not create track if there is no admission time
            return false;
        }

        if (!isset($fieldData[$fieldId])) {
            // Check from start if track has no field with this data ????
            // OR return true; ??
            return $this->createFromStart($agenda, $appointment, $filter, $respTrack, $filterTracer);
        }

        if ($fieldData[$fieldId] == $appointment->getId()) {
            if ($filterTracer) {
                $filterTracer->setSkipCreationMessage(
                    $this->translator->_('track has already been created')
                );
            }
            return false;
        }

        $appointmentId = $fieldData[$fieldId];
        $appointment = $agenda->getAppointment($appointmentId);
        $appointmentDate = $appointment->getAdmissionTime();

        if (!$appointmentDate) {
            // Check from start if track has no field with this data ????
            // OR return true; ??
            return $this->createFromStart($agenda, $appointment, $filter, $respTrack, $filterTracer);
        }

        $diff = $curr->diff($appointmentDate);
        if ($diff && $diff->days <= $wait) {
            if ($filterTracer) {
                $filterTracer->setSkipCreationMessage(sprintf(
                    $this->translator->_('%d days since previous track field date, %d required'),
                    $diff->days,
                    $wait
                ));
            }
            return false;
        }

        return true;
    }

    /**
     * Always return the track should NOT be created
     *
     * This should never be called as 0 is not a creator, the code is here just
     * to make sure calling without checking has the correct result
     */
    public function createNever(
        Agenda $agenda,
        Appointment $appointment,
        TrackFieldFilterCalculationInterface $filter,
        RespondentTrack $respTrack,
        FilterTracer|null $filterTracer = null
    ): bool
    {
        if ($filterTracer) {
            $filterTracer->setSkipCreationMessage($this->translator->_('never create a track'));
        }
        return false;
    }

    /**
     * Only return true when no open track existsd
     */
    public function createNoOpen(
        Agenda $agenda,
        Appointment $appointment,
        TrackFieldFilterCalculationInterface $filter,
        RespondentTrack $respTrack,
        FilterTracer|null $filterTracer = null
    ): bool
    {
        // If an open track of this type exists: do not create a new one
        $createTrack = !$respTrack->isOpen();

        if ($createTrack) {
            $createTrack = $this->createWhenNotInThisTrack($agenda, $appointment, $filter, $respTrack, $filterTracer);
        } elseif ($filterTracer) {
            $filterTracer->setSkipCreationMessage(
                $this->translator->_('an open track exists')
            );
        }

        return $createTrack;
    }

    /**
     * Only return true when no open track existsd
     */
    public function createWhenNoOpen(
        Agenda $agenda,
        Appointment $appointment,
        TrackFieldFilterCalculationInterface $filter,
        RespondentTrack $respTrack,
        FilterTracer|null $filterTracer = null
    ): bool
    {
        // If an open track of this type exists: do not create a new one
        $createTrack = !$respTrack->isOpen();

        if ($createTrack) {
            $createTrack = $this->createAfterWaitDays($agenda, $appointment, $filter, $respTrack, $filterTracer);
        } elseif ($filterTracer) {
            $filterTracer->setSkipCreationMessage(
                $this->translator->_('an open track exists')
            );
        }

        if ($createTrack) {
            $createTrack = $this->createWhenNotInThisTrack($agenda, $appointment, $filter, $respTrack, $filterTracer);
        }

        return $createTrack;
    }

    /**
     * Create when current appointment is not assigned to this field already
     */
    public function createWhenNotInThisTrack(
        Agenda $agenda,
        Appointment $appointment,
        TrackFieldFilterCalculationInterface $filter,
        RespondentTrack $respTrack,
        FilterTracer|null $filterTracer = null
    ): bool
    {
        $createTrack = true;

        $data = $respTrack->getFieldData();
        if (isset($data[$filter->getFieldId()]) &&
            ($appointment->getId() == $data[$filter->getFieldId()])) {
            $createTrack = false;

            if ($filterTracer) {
                $filterTracer->setSkipCreationMessage(
                    $this->translator->_('appointment used in track')
                );
            }
        }

        return $createTrack;
    }

    public function shouldCreateTrack(
        Agenda $agenda,
        Appointment $appointment,
        TrackFieldFilterCalculationInterface $filter,
        RespondentTrack $respTrack,
        FilterTracer|null $filterTracer = null
    ): bool
    {
        return match($filter->getCreatorType()) {
            0 => $this->createNever($agenda, $appointment, $filter, $respTrack, $filterTracer),
            1 => $this->createWhenNoOpen($agenda, $appointment, $filter, $respTrack, $filterTracer),
            2 => $this->createAlways($agenda, $appointment, $filter, $respTrack, $filterTracer),
            3 => $this->createAlwaysNoEndDate($agenda, $appointment, $filter, $respTrack, $filterTracer),
            4 => $this->createFromStart($agenda, $appointment, $filter, $respTrack, $filterTracer),
            5 => $this->createNoOpen($agenda, $appointment, $filter, $respTrack, $filterTracer),
            6 => $this->createFromCurrentField($agenda, $appointment, $filter, $respTrack, $filterTracer),
            default => false,
        };
    }
}