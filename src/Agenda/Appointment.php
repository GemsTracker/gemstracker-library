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
    public function getActivityId(): int|null
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
    public function getAttendedById(): int|null
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
    public function getEpisodeId(): int|null
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
     * Return the appointment info array
     *
     * @return array
     */
    public function getInfo(): array
    {
        if (isset($this->data['gap_info'])) {
            if (is_string($this->data['gap_info'])) {
                return json_decode($this->data['gap_info'], true);
            }
            if (is_array($this->data['gap_info'])) {
                return $this->data['gap_info'];
            }
        }
        return [];
    }

    /**
     * Return the description of the current location
     *
     * @return string or null when not found
     */
    public function getLocationDescription(): string|null
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
    public function getLocationId(): int|null
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
}