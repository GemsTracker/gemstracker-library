<?php

namespace Gems\Agenda\Repository;

use Gems\Agenda\Appointment;
use Gems\Agenda\Filter\TrackFieldFilterCalculationInterface;
use Gems\Agenda\FilterTracer;
use Gems\Tracker\RespondentTrack;
use MUtil\Translate\Translator;

class FilterCreateTrackChecker
{
    public function __construct(
        protected readonly Translator $translator,
    )
    {}

    /**
     * Has the track ended <wait days> ago?
     *
     * @param TrackFieldFilterCalculationInterface $filter
     * @param RespondentTrack $respTrack
     *
     * @return bool
     */
    public function createAfterWaitDays(
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
     *
     * @param TrackFieldFilterCalculationInterface $filter
     * @param RespondentTrack $respTrack
     *
     * @return boolean
     */
    public function createAlways(
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
     *
     * @param TrackFieldFilterCalculationInterface $filter
     * @param RespondentTrack $respTrack
     *
     * @return boolean
     */
    public function createAlwaysNoEndDate(
        Appointment $appointment,
        TrackFieldFilterCalculationInterface $filter,
        RespondentTrack $respTrack,
        FilterTracer|null $filterTracer = null
    ): bool
    {
        return $this->createWhenNotInThisTrack($appointment, $filter, $respTrack, $filterTracer);
    }

    /**
     * Always report the track should be created
     *
     * @param TrackFieldFilterCalculationInterface $filter
     * @param RespondentTrack $respTrack
     * @return boolean
     */
    public function createFromStart(
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

    /**
     * Always return the track should NOT be created
     *
     * This should never be called as 0 is not a creator, the code is here just
     * to make sure calling without checking has the correct result
     *
     * @param TrackFieldFilterCalculationInterface $filter
     * @param RespondentTrack $respTrack
     *
     * @return boolean
     */
    public function createNever(
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
     * Only return true when no open track exists
     *
     * @param TrackFieldFilterCalculationInterface $filter
     * @param RespondentTrack $respTrack
     *
     * @return boolean
     */
    public function createNoOpen(
        Appointment $appointment,
        TrackFieldFilterCalculationInterface $filter,
        RespondentTrack $respTrack,
        FilterTracer|null $filterTracer = null
    ): bool
    {
        // If an open track of this type exists: do not create a new one
        $createTrack = !$respTrack->isOpen();

        if ($createTrack) {
            $createTrack = $this->createWhenNotInThisTrack($appointment, $filter, $respTrack, $filterTracer);
        } elseif ($filterTracer) {
            $filterTracer->setSkipCreationMessage(
                $this->translator->_('an open track exists')
            );
        }

        return $createTrack;
    }

    /**
     * Only return true when no open track exists
     *
     * @param TrackFieldFilterCalculationInterface $filter
     * @param RespondentTrack $respTrack
     *
     * @return boolean
     */
    public function createWhenNoOpen(
        Appointment $appointment,
        TrackFieldFilterCalculationInterface $filter,
        RespondentTrack $respTrack,
        FilterTracer|null $filterTracer = null
    ): bool
    {
        // If an open track of this type exists: do not create a new one
        $createTrack = !$respTrack->isOpen();

        if ($createTrack) {
            $createTrack = $this->createAfterWaitDays($appointment, $filter, $respTrack, $filterTracer);
        } elseif ($filterTracer) {
            $filterTracer->setSkipCreationMessage(
                $this->translator->_('an open track exists')
            );
        }

        if ($createTrack) {
            $createTrack = $this->createWhenNotInThisTrack($appointment, $filter, $respTrack, $filterTracer);
        }

        return $createTrack;
    }

    /**
     * Create when current appointment is not assigned to this field already
     *
     * @param TrackFieldFilterCalculationInterface $filter
     * @param RespondentTrack $respTrack
     *
     * @return boolean
     */
    public function createWhenNotInThisTrack(
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
        Appointment $appointment,
        TrackFieldFilterCalculationInterface $filter,
        RespondentTrack $respTrack,
        FilterTracer|null $filterTracer = null
    ): bool
    {
        return match($filter->getCreatorType()) {
            0 => $this->createNever($appointment, $filter, $respTrack, $filterTracer),
            1 => $this->createWhenNoOpen($appointment, $filter, $respTrack, $filterTracer),
            2 => $this->createAlways($appointment, $filter, $respTrack, $filterTracer),
            3 => $this->createAlwaysNoEndDate($appointment, $filter, $respTrack, $filterTracer),
            4 => $this->createFromStart($appointment, $filter, $respTrack, $filterTracer),
            5 => $this->createNoOpen($appointment, $filter, $respTrack, $filterTracer),
            default => false,
        };
    }
}