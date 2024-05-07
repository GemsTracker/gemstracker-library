<?php

/**
 *
 * @package    Gems
 * @subpackage Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2017, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Agenda;

use DateTimeInterface;
use Gems\Repository\RespondentRepository;
use MUtil\Model;
use Zalt\Base\TranslatorInterface;

/**
 *
 * @package    Gems
 * @subpackage Agenda
 * @copyright  Copyright (c) 2017, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.4 16-May-2018 17:55:13
 */
class EpisodeOfCare
{
    /**
     *
     * @var array appointmentId => appointment object
     */
    protected array|null $appointments = null;

    /**
     *
     * @var int The id of the episode
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
     * Creates the episode of care object
     *
     * @param array $episodeData Episode Id or array containing episode record
     */
    public function __construct(
        array $episodeData,
        protected readonly Agenda $agenda,
        protected readonly RespondentRepository $respondentRepository,
        protected readonly TranslatorInterface $translator,
    )
    {
        $this->data      = $episodeData;
        $this->id     = $episodeData['gec_episode_of_care_id'];

        /*if (is_array($episodeData)) {
            $this->data      = $episodeData;
            $this->_episodeId     = $episodeData['gec_episode_of_care_id'];
        } else {
            $this->_episodeId = $episodeData;
            // loading occurs in checkRegistryRequestAnswers
        }*/
    }


    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required are missing.
     */
    /*public function checkRegistryRequestsAnswers()
    {
        $this->maskRepository = $this->loader->getMaskRepository();

        if ($this->db && (! $this->data)) {
            $this->refresh();
        } else {
            $this->data = $this->maskRepository->applyMaskToRow($this->data);
        }

        return $this->exists;
    }*/

    /**
     *
     * @return array of appointmentId => appointment object
     */
    public function getAppointments(): array
    {
        if (null === $this->appointments) {
            $this->appointments = $this->agenda->getAppointmentsForEpisode($this);
        }

        return $this->appointments;
    }

    /**
     * The diagnosis of the episode
     *
     * @return string|null
     */
    public function getDiagnosis(): string|null
    {
        return isset($this->data['gec_diagnosis']) ? $this->data['gec_diagnosis'] : null;
    }

    /**
     * The diagnosis data of the episode translated from Json
     *
     * @return array of Json data
     */
    public function getDiagnosisData(): array
    {
        if (! isset($this->data['gec_diagnosis'])) {
            return [];
        }
        if (is_string($this->data['gec_diagnosis'])) {
            $this->data['gec_diagnosis'] = json_decode($this->data['gec_diagnosis'], true);
        }

        return $this->data['gec_diagnosis'];
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
        $results[] = $this->getStartDate()->format($this->agenda->episodeDisplayFormat);
        $results[] = $this->getSubject();
        $results[] = $this->getDiagnosis();

        return implode($this->translator->_('; '), array_filter($results));
    }

    /**
     * The extra data of the episode translated from Json
     *
     * @return array of Json data
     */
    public function getExtraData(): array
    {
        if (! isset($this->data['gec_extra_data'])) {
            return [];
        }
        if (is_string($this->data['gec_extra_data'])) {
            $this->data['gec_extra_data'] = json_decode($this->data['gec_extra_data'], true);
        }

        return $this->data['gec_extra_data'];
    }

    /**
     * Return the episode id
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     *
     * @return int
     */
    public function getOrganizationId(): int
    {
        return $this->data['gec_id_organization'];
    }

    /**
     * Return the respondent object
     *
     * @return \Gems\Tracker\Respondent
     */
    public function getRespondent()
    {
        return $this->respondentRepository->getRespondent(
            null,
            $this->getOrganizationId(),
            $this->getRespondentId()
        );
    }

    /**
     * Return the user / respondent id
     *
     * @return int
     */
    public function getRespondentId(): int
    {
        return $this->data['gec_id_user'];
    }

    /**
     * Return the start date
     *
     * @return ?DateTimeInterface Start date as a date or null
     */
    public function getStartDate(): DateTimeInterface|null
    {
        if (isset($this->data['gec_startdate']) && $this->data['gec_startdate']) {
            if (! $this->data['gec_startdate'] instanceof DateTimeInterface) {
                $this->data['gec_startdate'] = Model::getDateTimeInterface($this->data['gec_startdate']);
            }
            return $this->data['gec_startdate'];
        }
        return null;
    }

    /**
     * The subject of the episode
     *
     * @return string|null
     */
    public function getSubject(): string|null
    {
        return isset($this->data['gec_subject']) ? $this->data['gec_subject'] : null;
    }
}
