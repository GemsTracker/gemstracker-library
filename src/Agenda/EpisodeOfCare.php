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
use Gems\User\Mask\MaskRepository;
use MUtil\Model;

/**
 *
 * @package    Gems
 * @subpackage Agenda
 * @copyright  Copyright (c) 2017, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.4 16-May-2018 17:55:13
 */
class EpisodeOfCare extends \MUtil\Translate\TranslateableAbstract
{
    /**
     *
     * @var array appointmentId => appointment object
     */
    protected $_appointments = false;

    /**
     *
     * @var int The id of the episode
     */
    protected $_episodeId;

    /**
     *
     * @var array The gems appointment data
     */
    protected $_gemsData = array();

    /**
     *
     * @var \Gems\Agenda\Agenda
     */
    protected $agenda;

    /**
     *
     * @var \Gems\User\User
     */
    protected $currentUser;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * True when the token does exist.
     *
     * @var boolean
     */
    public $exists = true;

    /**
     *
     * @var \Gems\Loader
     */
    protected $loader;

    protected MaskRepository $maskRepository;

    /**
     * Creates the episode of care object
     *
     * @param mixed $episodeData Episode Id or array containing episode record
     */
    public function __construct($episodeData)
    {
        if (is_array($episodeData)) {
            $this->_gemsData      = $episodeData;
            $this->_episodeId     = $episodeData['gec_episode_of_care_id'];
        } else {
            $this->_episodeId = $episodeData;
            // loading occurs in checkRegistryRequestAnswers
        }
    }


    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        $this->maskRepository = $this->loader->getMaskRepository();

        if ($this->db && (! $this->_gemsData)) {
            $this->refresh();
        } else {
            $this->_gemsData = $this->maskRepository->applyMaskToRow($this->_gemsData);
        }

        return $this->exists;
    }

    /**
     *
     * @return array of appointmentId => appointment object
     */
    public function getAppointments()
    {
        if (false === $this->_appointments) {
            $this->_appointments = $this->agenda->getAppointmentsForEpisode($this);
        }

        return $this->_appointments;
    }

    /**
     * The diagnosis of the episode
     *
     * @return string
     */
    public function getDiagnosis()
    {
        return isset($this->_gemsData['gec_diagnosis']) ? $this->_gemsData['gec_diagnosis'] : null;
    }

    /**
     * The diagnosis data of the episode translated from Json
     *
     * @return array of Json data
     */
    public function getDiagnosisData()
    {
        if (! isset($this->_gemsData['gec_diagnosis'])) {
            return [];
        }
        if (is_string($this->_gemsData['gec_diagnosis'])) {
            $this->_gemsData['gec_diagnosis'] = json_decode($this->_gemsData['gec_diagnosis'], true);
        }

        return $this->_gemsData['gec_diagnosis'];
    }

    /**
     * Get a general description of this appointment
     *
     * @see \Gems\Agenda->getAppointmentDisplay()
     *
     * @return string
     */
    public function getDisplayString()
    {
        $results[] = $this->getStartDate()->format($this->agenda->episodeDisplayFormat);
        $results[] = $this->getSubject();
        $results[] = $this->getDiagnosis();

        return implode($this->_('; '), array_filter($results));
    }

    /**
     * The extra data of the episode translated from Json
     *
     * @return array of Json data
     */
    public function getExtraData()
    {
        if (! isset($this->_gemsData['gec_extra_data'])) {
            return [];
        }
        if (is_string($this->_gemsData['gec_extra_data'])) {
            $this->_gemsData['gec_extra_data'] = json_decode($this->_gemsData['gec_extra_data'], true);
        }

        return $this->_gemsData['gec_extra_data'];
    }

    /**
     * Return the episode id
     *
     * @return int
     */
    public function getId()
    {
        return $this->_episodeId;
    }

    /**
     *
     * @return int
     */
    public function getOrganizationId()
    {
        return $this->_gemsData['gec_id_organization'];
    }

    /**
     * Return the respondent object
     *
     * @return \Gems\Tracker\Respondent
     */
    public function getRespondent()
    {
        return $this->loader->getRespondent(
                null,
                $this->getOrganizationId(),
                $this->getRespondentId());
    }

    /**
     * Return the user / respondent id
     *
     * @return int
     */
    public function getRespondentId()
    {
        return $this->_gemsData['gec_id_user'];
    }

    /**
     * Return the start date
     *
     * @return ?DateTimeInterface Start date as a date or null
     */
    public function getStartDate(): ?DateTimeInterface
    {
        if (isset($this->_gemsData['gec_startdate']) && $this->_gemsData['gec_startdate']) {
            if (! $this->_gemsData['gec_startdate'] instanceof DateTimeInterface) {
                $this->_gemsData['gec_startdate'] = Model::getDateTimeInterface($this->_gemsData['gec_startdate']);
            }
            return $this->_gemsData['gec_startdate'];
        }
        return null;
    }

    /**
     * The subject of the episode
     *
     * @return string
     */
    public function getSubject()
    {
        return isset($this->_gemsData['gec_subject']) ? $this->_gemsData['gec_subject'] : null;
    }

    /**
     *
     * @param array $gemsData Optional, the data refresh with, otherwise refresh from database.
     * @return \Gems\Agenda\Appointment (continuation pattern)
     */
    public function refresh(array $gemsData = null)
    {
        if (is_array($gemsData)) {
            $this->_gemsData = $gemsData + $this->_gemsData;
        } else {
            $select = $this->db->select();
            $select->from('gems__episodes_of_care')
                    ->where('gec_episode_of_care_id = ?', $this->_episodeId);

            $this->_gemsData = $this->db->fetchRow($select);
            if (false == $this->_gemsData) {
                // on failure, reset to empty array
                $this->_gemsData = array();
            }
        }
        $this->exists = isset($this->_gemsData['gec_episode_of_care_id']);

        $this->_gemsData = $this->maskRepository->applyMaskToRow($this->_gemsData);
        $this->_appointments = false;

        return $this;
    }
}