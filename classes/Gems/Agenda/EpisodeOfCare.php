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

/**
 *
 * @package    Gems
 * @subpackage Agenda
 * @copyright  Copyright (c) 2017, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.4 16-May-2018 17:55:13
 */
class EpisodeOfCare extends \MUtil_Translate_TranslateableAbstract
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
     * @var \Gems_Agenda
     */
    protected $agenda;

    /**
     *
     * @var \Gems_User_User
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
     * @var \Gems_Loader
     */
    protected $loader;

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
            if ($this->currentUser instanceof \Gems_User_User) {
                $this->_gemsData = $this->currentUser->applyGroupMask($this->_gemsData);
            }
        } else {
            $this->_episodeId = $episodeData;
            // loading occurs in checkRegistryRequestAnswers
        }
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
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        if ($this->db && (! $this->_gemsData)) {
            $this->refresh();
        }

        return $this->exists;
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
     * @see \Gems_Agenda->getAppointmentDisplay()
     *
     * @return string
     */
    public function getDisplayString()
    {
        $results[] = $this->getStartDate()->toString($this->agenda->episodeDisplayFormat);
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
     * @return \Gems_Tracker_Respondent
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
     * @return \MUtil_Date Start date as a date or null
     */
    public function getStartDate()
    {
        if (isset($this->_gemsData['gec_startdate']) && $this->_gemsData['gec_startdate']) {
            if (! $this->_gemsData['gec_startdate'] instanceof \MUtil_Date) {
                $this->_gemsData['gec_startdate'] =
                        new \MUtil_Date($this->_gemsData['gec_startdate'], \Gems_Tracker::DB_DATE_FORMAT);
            }
            return $this->_gemsData['gec_startdate'];
        }
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
     * @return \Gems_Agenda_Appointment (continuation pattern)
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

        if ($this->currentUser instanceof \Gems_User_User) {
            $this->_gemsData = $this->currentUser->applyGroupMask($this->_gemsData);
        }
        $this->_appointments = false;

        return $this;
    }
}
