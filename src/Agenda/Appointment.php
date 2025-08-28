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
use Zalt\Base\TranslatorInterface;

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
     * @var int The id of the appointment
     */
    protected int $id;

    /**
     * @var array The gems appointment data
     */
    protected array $data = [];

    /**
     * True when the token does exist.
     */
    public bool $exists = true;

    protected FilterTracer|null $filterTracer = null;

    /**
     * Creates the appointments object
     *
     * @param array $appointmentData Appointment Id or array containing appointment record
     */
    public function __construct(
        protected readonly array $appointmentData,
        protected readonly TranslatorInterface $translator,
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
     * @return string|null description of the current activity or null when not found
     */
    public function getActivityDescription(): string|null
    {
        if (! (isset($this->data['gap_id_activity']) && $this->data['gap_id_activity'])) {
            $this->data['gaa_name'] = null;
        }
        if (!array_key_exists('gaa_name', $this->data)) {
            $this->data['gaa_name'] = $this->activityRepository->getActivityName($this->data['gap_id_activity']);
        }
        return $this->data['gaa_name'];
    }

    /**
     * @return int|null current activity ID
     */
    public function getActivityId(): int|null
    {
        return $this->data['gap_id_activity'];
    }

    /**
     * @return DateTimeInterface|null Admission time as a date or null
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
     * @return int|null DB id of the attending by person
     */
    public function getAttendedById(): int|null
    {
        return $this->data['gap_id_attended_by'];
    }

    /**
     * @return string The comment to the appointment
     */
    public function getComment(): string
    {
        return $this->data['gap_comment'];
    }

    /**
     * Get a general description of this appointment
     *
     * @see \Gems\Agenda->getAppointmentDisplay()
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

    public function getEpisode(): EpisodeOfCare|null
    {
        $episodeId = $this->getEpisodeId();

        if ($episodeId) {
            return $this->agenda->getEpisodeOfCare($episodeId);
        }

        return null;
    }

    public function getEpisodeId(): int|null
    {
        return $this->data['gap_id_episode'];
    }

    /**
     * @return int appointment id
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return array appointment info array
     */
    public function getInfo(): array
    {
        if (isset($this->data['gap_info'])) {
            if (is_string($this->data['gap_info'])) {
                return json_decode($this->data['gap_info'], true) ?? [];
            }
            if (is_array($this->data['gap_info'])) {
                return $this->data['gap_info'];
            }
        }
        return [];
    }

    /**
     * @return string|null Return the description of the current location or null when not found
     */
    public function getLocationDescription(): string|null
    {
        if (! (isset($this->data['gap_id_location']) && $this->data['gap_id_location'])) {
            $this->data['glo_name'] = null;
        }
        if (!array_key_exists('glo_name', $this->data)) {
            $this->data['glo_name'] = $this->locationRepository->getLocationName($this->data['gap_id_location']);
        }
        return $this->data['glo_name'];
    }

    /**
     * @return int DB id of the location
     */
    public function getLocationId(): int|null
    {
        return $this->data['gap_id_location'];
    }

    public function getOrganizationId(): int
    {
        return $this->data['gap_id_organization'];
    }

    /**
     * @return string|null The respondents patient number
     */
    public function getPatientNumber(): string|null
    {
        if (! isset($this->data['gr2o_patient_nr'])) {
            $this->data['gr2o_patient_nr'] = $this->respondentRepository->getPatientNr($this->data['gap_id_user'], $this->data['gap_id_organization']);
        }

        return $this->data['gr2o_patient_nr'];
    }

    /**
     * Return the description of the current procedure or null when not found
     */
    public function getProcedureDescription(): string|null
    {
        if (! (isset($this->data['gap_id_procedure']) && $this->data['gap_id_procedure'])) {
            return null;
        }
        if (!array_key_exists('gapr_name', $this->data)) {
            $this->data['gapr_name'] = $this->procedureRepository->getProcedureName($this->data['gap_id_procedure']);
        }
        return $this->data['gapr_name'];
    }

    /**
     * Return the id of the current procedure
     */
    public function getProcedureId(): int|null
    {
        return $this->data['gap_id_procedure'];
    }

    /**
     * Get the DB id of the referred by person
     */
    public function getReferredById(): int|null
    {
        return $this->data['gap_id_referred_by'];
    }

    /**
     * Return the respondent object
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
     */
    public function getRespondentId(): int
    {
        return $this->data['gap_id_user'];
    }

    /**
     * The source of the appointment
     */
    public function getSource(): string
    {
        return $this->data['gap_source'];
    }

    /**
     * The source id of the appointment
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