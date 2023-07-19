<?php

/**
 *
 * @package    Gems
 * @subpackage Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Agenda;

use DateTimeImmutable;
use DateTimeInterface;
use Gems\Agenda\Repository\ActivityRepository;
use Gems\Agenda\Repository\LocationRepository;
use Gems\Agenda\Repository\ProcedureRepository;
use Gems\Repository\RespondentRepository;
use Gems\Tracker;
use Gems\Tracker\Respondent;
use Gems\Tracker\RespondentTrack;
use MUtil\Translate\Translator;

/**
 *
 * @package    Gems
 * @subpackage Agenda
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.3
 */
class Appointment
{
    /**
     *
     * @var int The id of the appointment
     */
    protected int $id;

    /**
     *
     * @var array The gems appointment data
     */
    protected array $data = [];

    /**
     * True when the token does exist.
     *
     * @var boolean
     */
    public bool $exists = true;

    /**
     *
     * @var FilterTracer
     */
    protected FilterTracer|null $filterTracer = null;

    /**
     * Creates the appointments object
     *
     * @param mixed $appointmentData Appointment Id or array containing appointment record
     */
    public function __construct(
        protected readonly array $appointmentData,
        protected readonly Translator $translator,
        protected readonly Agenda $agenda,
        protected readonly ActivityRepository $activityRepository,
        protected readonly LocationRepository $locationRepository,
        protected readonly ProcedureRepository $procedureRepository,
        protected readonly RespondentRepository $respondentRepository,

    )
    {
        $this->data = $appointmentData;
        $this->id = $appointmentData['gap_id_appointment'];
    }

    /**
     * Create a new track for this appointment and the given filter
     *
     * @param \Gems\Agenda\AppointmentFilterInterface $filter
     * @param \Gems\Tracker $tracker
     */
    protected function _createTrack(AppointmentFilterInterface $filter, Tracker $tracker): RespondentTrack
    {
        $trackData = [
            'gr2t_comment' => sprintf(
                $this->translator->_('Track created by %s filter'),
                $filter->getName()
            ),
        ];

        $fields    = [
            $filter->getFieldId() => $this->getId()
        ];
        $trackId   = $filter->getTrackId();
        $respTrack = $tracker->createRespondentTrack(
                $this->getRespondentId(),
                $this->getOrganizationId(),
                $trackId,
                null,
                $trackData,
                $fields
                );

        return $respTrack;
    }

    /**
     * Check if a track should be created for any of the filters
     *
     * @param \Gems\Agenda\AppointmentFilterInterface[] $filters
     * @param array $existingTracks Of $trackId => [RespondentTrack objects]
     * @param \Gems\Tracker $tracker
     *
     * @return int Number of tokenchanges
     */
    protected function checkCreateTracks(AppointmentFilterInterface $filters, array $existingTracks, Tracker $tracker): int
    {
        $tokenChanges = 0;

        // Check for tracks that should be created
        foreach ($filters as $filter) {
            if (!$filter->isCreator()) {
                continue;
            }

            $createTrack = true;

            // Find the method to use for this creator type
            $method      = $this->getCreatorCheckMethod($filter->getCreatorType());
            $trackId     = $filter->getTrackId();
            $tracks      = array_key_exists($trackId, $existingTracks) ? $existingTracks[$trackId] : [];

            foreach($tracks as $respTrack) {
                /* @var $respTrack \Gems\Tracker\RespondentTrack */
                if (!$respTrack->hasSuccesCode()) {
                    continue;
                }

                $createTrack = $this->$method($filter, $respTrack);
                if ($createTrack === false) {
                    break;  // Stop checking
                }
            }
            if ($this->filterTracer) {
                $this->filterTracer->addFilter($filter, $createTrack, $respTrack);

                if (! $this->filterTracer->executeChanges) {
                    $createTrack = false;
                }
            }

            // \MUtil\EchoOut\EchoOut::track($trackId, $createTrack, $filter->getName(), $filter->getSqlAppointmentsWhere(), $filter->getFilterId());
            if ($createTrack) {
                $respTrack = $this->_createTrack($filter, $tracker);
                $existingTracks[$trackId][] = $respTrack;

                $tokenChanges += $respTrack->getCount();
                if ($this->filterTracer) {
                    $this->filterTracer->addFilter($filter, $createTrack, $respTrack);
                }
            }
        }

        return $tokenChanges;
    }

    /**
     * Has the track ended <wait days> ago?
     *
     * @param \Gems\Agenda\AppointmentFilterInterface $filter
     * @param \Gems\Tracker\RespondentTrack $respTrack
     *
     * @return boolean
     */
    public function createAfterWaitDays(AppointmentFilterInterface $filter, RespondentTrack $respTrack): bool
    {
        $createTrack = true;
        $curr        = $this->getAdmissionTime();
        $end         = $respTrack->getEndDate();
        $wait        = $filter->getWaitDays();

        if ($curr && $end) {
            $diff = $curr->diff($end);
        }
        
        if ((! $end) || ($diff->days <= $wait)) {
            $createTrack = false;
            if ($this->filterTracer) {
                if (! $end) {
                    $this->filterTracer->setSkipCreationMessage(
                            $this->translator->_('track without an end date')
                            );
                } else {
                    $this->filterTracer->setSkipCreationMessage(sprintf(
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
            if (isset($data[$fieldId]) && ($data[$fieldId] == $this->id)) {
                $createTrack = false;
                if ($this->filterTracer) {
                    $this->filterTracer->setSkipCreationMessage(
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
     * @param \Gems\Agenda\AppointmentFilterInterface $filter
     * @param \Gems\Tracker\RespondentTrack $respTrack
     *
     * @return boolean
     */
    public function createAlways(AppointmentFilterInterface $filter, RespondentTrack $respTrack): bool
    {
        $createTrack = $this->createAfterWaitDays($filter, $respTrack);

        if ($createTrack) {
            $createTrack = $this->createWhenNotInThisTrack($filter, $respTrack);
        }

        return $createTrack;
    }

    /**
     * Always report the track should be created
     *
     * @param \Gems\Agenda\AppointmentFilterInterface $filter
     * @param \Gems\Tracker\RespondentTrack $respTrack
     *
     * @return boolean
     */
    public function createAlwaysNoEndDate(AppointmentFilterInterface $filter, RespondentTrack $respTrack): bool
    {
        return $this->createWhenNotInThisTrack($filter, $respTrack);
    }

    /**
     * Always report the track should be created
     *
     * @param \Gems\Agenda\AppointmentFilterInterface $filter
     * @param \Gems\Tracker\RespondentTrack $respTrack
     * @return boolean
     */
    public function createFromStart(AppointmentFilterInterface $filter, RespondentTrack $respTrack): bool
    {
        $createTrack = true;
        $curr        = $this->getAdmissionTime();
        $start       = $respTrack->getStartDate();
        $wait        = $filter->getWaitDays();

        if ($curr && $start) {
            $diff = $curr->diff($start);
        }

        if ((! $start) || ($diff->days <= $wait)) {
            $createTrack = false;
            if ($this->filterTracer) {
                if (! $start) {
                    $this->filterTracer->setSkipCreationMessage(
                            $this->translator->_('track without a startdate')
                            );
                } else {
                    $this->filterTracer->setSkipCreationMessage(sprintf(
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
            if (isset($data[$fieldId]) && ($data[$fieldId] == $this->id)) {
                $createTrack = false;
                if ($this->filterTracer) {
                    $this->filterTracer->setSkipCreationMessage(
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
     * @param \Gems\Agenda\AppointmentFilterInterface $filter
     * @param \Gems\Tracker\RespondentTrack $respTrack
     *
     * @return boolean
     */
    public function createNever(): bool
    {
        if ($this->filterTracer) {
            $this->filterTracer->setSkipCreationMessage($this->translator->_('never create a track'));
        }
        return false;
    }

    /**
     * Only return true when no open track exists
     *
     * @param \Gems\Agenda\AppointmentFilterInterface $filter
     * @param \Gems\Tracker\RespondentTrack $respTrack
     *
     * @return boolean
     */
    public function createNoOpen(AppointmentFilterInterface $filter, RespondentTrack $respTrack): bool
    {
        // If an open track of this type exists: do not create a new one
        $createTrack = !$respTrack->isOpen();

        if ($createTrack) {
            $createTrack = $this->createWhenNotInThisTrack($filter, $respTrack);
        } elseif ($this->filterTracer) {
            $this->filterTracer->setSkipCreationMessage(
                    $this->translator->_('an open track exists')
                    );
        }

        return $createTrack;
    }

    /**
     * Create when current appointment is not assigned to this field already
     *
     * @param \Gems\Agenda\AppointmentFilterInterface $filter
     * @param \Gems\Tracker\RespondentTrack $respTrack
     *
     * @return boolean
     */
    public function createWhenNotInThisTrack(AppointmentFilterInterface $filter, RespondentTrack $respTrack): bool
    {
        $createTrack = true;

        $data = $respTrack->getFieldData();
        if (isset($data[$filter->getFieldId()]) &&
                ($this->getId() == $data[$filter->getFieldId()])) {
            $createTrack = false;

            if ($this->filterTracer) {
                $this->filterTracer->setSkipCreationMessage(
                        $this->_('appointment used in track')
                        );
            }
        }

        return $createTrack;
    }

    /**
     * Only return true when no open track exists
     *
     * @param \Gems\Agenda\AppointmentFilterInterface $filter
     * @param \Gems\Tracker\RespondentTrack $respTrack
     *
     * @return boolean
     */
    public function createWhenNoOpen(AppointmentFilterInterface $filter, RespondentTrack $respTrack): bool
    {
        // If an open track of this type exists: do not create a new one
        $createTrack = !$respTrack->isOpen();

        if ($createTrack) {
            $createTrack = $this->createAfterWaitDays($filter, $respTrack);
        } elseif ($this->filterTracer) {
            $this->filterTracer->setSkipCreationMessage(
                    $this->translator->_('an open track exists')
                    );
        }

        if ($createTrack) {
            $createTrack = $this->createWhenNotInThisTrack($filter, $respTrack);
        }

        return $createTrack;
    }

    /**
     * Return the description of the current ativity
     *
     * @return string or null when not found
     */
    public function getActivityDescription(): string|null
    {
        if (! (isset($this->data['gap_id_activity']) && $this->data['gap_id_activity'])) {
            $this->data['gaa_name'] = null;
        }
        if (!array_key_exists('gaa_name', $this->data)) {
            $this->data['gaa_name'] = $this->activityRepository->getActivityName($this->data['gap_id_activity']);

            // Cleanup db result
            if (false === $this->data['gaa_name']) {
                $this->data['gaa_name'] = null;
            }
        }
        return $this->data['gaa_name'];
    }

    /**
     * Return the id of the current activity
     *
     * @return int
     */
    public function getActivityId(): int
    {
        return $this->data['gap_id_activity'];
    }

    /**
     * Return the admission time
     *
     * @return ?DateTimeInterface Admission time as a date or null
     */
    public function getAdmissionTime(): DateTimeInterface|null
    {
        if (isset($this->data['gap_admission_time']) && $this->data['gap_admission_time']) {
            if (! $this->data['gap_admission_time'] instanceof DateTimeInterface) {
                $this->data['gap_admission_time'] =
                        DateTimeImmutable::createFromFormat(Tracker::DB_DATETIME_FORMAT, $this->data['gap_admission_time']);
            }
            // Clone to make sure calculations can be performed without changing this object
            return $this->data['gap_admission_time'];
        }
        return null;
    }

    /**
     * Get the DB id of the attending by person
     *
     * @return int
     */
    public function getAttendedById(): int
    {
        return $this->data['gap_id_attended_by'];
    }

    /**
     * The cooment to the appointment
     *
     * @return string
     */
    public function getComment(): string
    {
        return $this->data['gap_comment'];
    }

    /**
     * Get method to call to check track creation
     *
     * @param int $type
     * @return string The method to call in this class
     */
    public function getCreatorCheckMethod(int $type): string
    {
        static $methods = [
            0 => 'createNever',
            1 => 'createWhenNoOpen',
            2 => 'createAlways',
            3 => 'createAlwaysNoEndDate',
            4 => 'createFromStart',
            5 => 'createNoOpen',
        ];

        // No checks, when type does not exists this is an error we want to be thrown
        return $methods[$type];
    }

    /**
     * Get a general description of this appointment
     *
     * @see \Gems\Agenda->getAppointmentDisplay()
     *
     * @return string
     */
    public function getDisplayString(): string
    {
        $results[] = $this->getAdmissionTime()->format($this->agenda->appointmentDisplayFormat);
        $results[] = $this->getActivityDescription();
        $results[] = $this->getProcedureDescription();
        $results[] = $this->getLocationDescription();
        $results[] = $this->getSubject();

        return implode($this->translator->_('; '), array_filter($results));
    }

    /**
     *
     * @return EpisodeOfCare|null
     */
    public function getEpisode(): EpisodeOfCare|null
    {
        $episodeId = $this->getEpisodeId();

        if ($episodeId) {
            return $this->agenda->getEpisodeOfCare($episodeId);
        }

        return null;
    }

    /**
     *
     * @return int
     */
    public function getEpisodeId(): int
    {
        return $this->data['gap_id_episode'];
    }

    /**
     * Return the appointment id
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Return the description of the current location
     *
     * @return string or null when not found
     */
    public function getLocationDescription(): string
    {
        if (! (isset($this->data['gap_id_location']) && $this->data['gap_id_location'])) {
            $this->data['glo_name'] = null;
        }
        if (!array_key_exists('glo_name', $this->data)) {
            $this->data['glo_name'] = $this->locationRepository->getLocationName($this->data['gap_id_location']);

            // Cleanup db result
            if (false === $this->data['glo_name']) {
                $this->data['glo_name'] = null;
            }
        }
        return $this->data['glo_name'];
    }

    /**
     * Get the DB id of the location
     *
     * @return int
     */
    public function getLocationId(): int
    {
        return $this->data['gap_id_location'];
    }

    /**
     *
     * @return int
     */
    public function getOrganizationId(): int
    {
        return $this->data['gap_id_organization'];
    }

    /**
     *
     * @return string The respondents patient number
     */
    public function getPatientNumber(): string|null
    {
        if (! isset($this->data['gr2o_patient_nr'])) {
            $this->data['gr2o_patient_nr'] = $this->respondentRepository->getPatientNr($this->data['gap_id_user'], $this->data['gap_id_organization']);
        }

        return $this->data['gr2o_patient_nr'];
    }

    /**
     * Return the description of the current procedure
     *
     * @return string or null when not found
     */
    public function getProcedureDescription()
    {
        if (! (isset($this->data['gap_id_procedure']) && $this->data['gap_id_procedure'])) {
            return null;
        }
        if (!array_key_exists('gapr_name', $this->data)) {
            $this->data['gapr_name'] = $this->procedureRepository->getProcedureName($this->data['gap_id_procedure']);

            // Cleanup db result
            if (false === $this->data['gapr_name']) {
                $this->data['gapr_name'] = null;
            }
        }
        return $this->data['gapr_name'];
    }

    /**
     * Return the id of the current procedure
     *
     * @return int
     */
    public function getProcedureId(): int|null
    {
        return $this->data['gap_id_procedure'];
    }

    /**
     * Get the DB id of the referred by person
     *
     * @return int
     */
    public function getReferredById(): int|null
    {
        return $this->data['gap_id_referred_by'];
    }

    /**
     * Return the respondent object
     *
     * @return \Gems\Tracker\Respondent
     */
    public function getRespondent(): Respondent
    {
        return $this->respondentRepository->getRespondent(
                $this->getPatientNumber(),
                $this->getOrganizationId(),
                $this->getRespondentId())
            ;
    }

    /**
     * Return the user / respondent id
     *
     * @return int
     */
    public function getRespondentId(): int
    {
        return $this->data['gap_id_user'];
    }

    /**
     * The source of the appointment
     *
     * @return string
     */
    public function getSource(): string
    {
        return $this->data['gap_source'];
    }

    /**
     * The source id of the appointment
     *
     * @return string
     */
    public function getSourceId(): string|int
    {
        return $this->data['gap_id_in_source'];
    }

    /**
     * The subject of the appointment
     *
     * @return string
     */
    public function getSubject(): string|null
    {
        return isset($this->data['gap_subject']) ? $this->data['gap_subject'] : null;
    }

    /**
     *
     * @return boolean
     */
    public function hasEpisode(): bool
    {
        return (boolean) $this->data['gap_id_episode'];
    }

    /**
     * Return true when the status is active
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->exists &&
                isset($this->data['gap_status']) &&
                $this->agenda->isStatusActive($this->data['gap_status']);
    }

    /**
     *
     * @param FilterTracer $tracer
     * @return $this
     */
    public function setFilterTracer(FilterTracer $tracer): self
    {
        $this->filterTracer = $tracer;

        return $this;
    }

    /**
     * Recalculate all tracks that use this appointment
     *
     * @return int The number of tokens changed by this code
     */
    public function updateTracks(): int
    {
        $tokenChanges = 0;
        $tracker      = $this->loader->getTracker();

        // Find all the fields that use this agenda item
        $select = $this->db->select();
        $select->from('gems__respondent2track2appointment', array('gr2t2a_id_respondent_track'))
                ->joinInner(
                        'gems__respondent2track',
                        'gr2t_id_respondent_track = gr2t2a_id_respondent_track',
                        array('gr2t_id_track')
                        )
                ->where('gr2t2a_id_appointment = ?', $this->id)
                ->distinct()
                ->order('gr2t_id_track');

        // AND find the filters for any new fields to fill
        $filters = $this->agenda->matchFilters($this);
        if ($filters) {
            $ids = array_map(function ($value) {
                return $value->getTrackId();
            }, $filters);

            // \MUtil\EchoOut\EchoOut::track(array_keys($filters), $ids);
            $respId = $this->getRespondentId();
            $orgId  = $this->getOrganizationId();
            $select->orWhere(
                    "gr2t_id_user = $respId AND gr2t_id_organization = $orgId AND gr2t_id_track IN (" .
                    implode(', ', $ids) . ")"
                    );
            // \MUtil\EchoOut\EchoOut::track($this->getId(), implode(', ', $ids));
        }

        // \MUtil\EchoOut\EchoOut::track($select->__toString());

        // Now find all the existing tracks that should be checked
        $respTracks = $this->db->fetchPairs($select);

        // \MUtil\EchoOut\EchoOut::track($respTracks);
        $existingTracks = array();
        if ($respTracks) {
            foreach ($respTracks as $respTrackId => $trackId) {
                $respTrack = $tracker->getRespondentTrack($respTrackId);

                // Recalculate this track
                $fieldsChanged = false;
                if ((! $this->filterTracer) || $this->filterTracer->executeChanges) {
                    $changed = $respTrack->recalculateFields($fieldsChanged);
                } else {
                    $changed = 0;
                }
                if ($this->filterTracer) {
                    $this->filterTracer->addTrackChecked($respTrack, $fieldsChanged, $changed);
                }
                $tokenChanges += $changed;

                // Store the track for creation checking
                $existingTracks[$trackId][] = $respTrack;
            }
        }

        // Only check if we need to create when this appointment is active and today or later
        if ($this->isActive() && ($this->getAdmissionTime()->getTimestamp() >= time())) {
            $tokenChanges += $this->checkCreateTracks($filters, $existingTracks, $tracker);
        } else {
            if ($this->filterTracer) {
                $this->filterTracer->setSkippedFilterCheck();
            }
        }

        return $tokenChanges;
    }
}