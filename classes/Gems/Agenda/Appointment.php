<?php

/**
 *
 * @package    Gems
 * @subpackage Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

use Gems\Agenda\AppointmentFilterInterface;

use Gems\Agenda\EpisodeOfCare;

/**
 *
 * @package    Gems
 * @subpackage Agenda
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.3
 */
class Gems_Agenda_Appointment extends \MUtil_Translate_TranslateableAbstract
{
    /**
     *
     * @var int The id of the appointment
     */
    protected $_appointmentId;

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
     * Creates the appointments object
     *
     * @param mixed $appointmentData Appointment Id or array containing appointment record
     */
    public function __construct($appointmentData)
    {
        if (is_array($appointmentData)) {
            $this->_gemsData      = $appointmentData;
            $this->_appointmentId = $appointmentData['gap_id_appointment'];
            if ($this->currentUser instanceof \Gems_User_User) {
                $this->_gemsData = $this->currentUser->applyGroupMask($this->_gemsData);
            }
        } else {
            $this->_appointmentId = $appointmentData;
            // loading occurs in checkRegistryRequestAnswers
        }
    }

    /**
     * Create a new track for this appointment and the given filter
     *
     * @param \Gems\Agenda\AppointmentFilterInterface $filter
     * @param \Gems_Tracker $tracker
     */
    protected function _createTrack($filter, $tracker)
    {
        $trackData = array('gr2t_comment' => sprintf(
                        $this->_('Track created by %s filter'),
                        $filter->getName()
                        ));

        $fields    = array($filter->getFieldId() => $this->getId());
        $trackId   = $filter->getTrackId();
        $respTrack = $tracker->createRespondentTrack(
                $this->getRespondentId(),
                $this->getOrganizationId(),
                $trackId,
                $this->currentUser->getUserId(),
                $trackData,
                $fields
                );

        return $respTrack;
    }

    /**
     * Makes sure the respondent data is part of the $this->_gemsData
     */
    protected function _ensureRespondentOrgData()
    {
        if (! isset($this->_gemsData['gr2o_id_user'], $this->_gemsData['gco_code'])) {
            $sql = "SELECT *
                FROM gems__respondents INNER JOIN
                    gems__respondent2org ON grs_id_user = gr2o_id_user INNER JOIN
                    gems__consents ON gr2o_consent = gco_description
                WHERE gr2o_id_user = ? AND gr2o_id_organization = ? LIMIT 1";

            $respId = $this->_gemsData['gap_id_user'];
            $orgId  = $this->_gemsData['gap_id_organization'];
            // \MUtil_Echo::track($this->_gemsData);

            if ($row = $this->db->fetchRow($sql, array($respId, $orgId))) {
                $this->_gemsData = $this->_gemsData + $row;
            } else {
                $appId = $this->_appointmentId;
                throw new \Gems_Exception("Respondent data missing for appointment id $appId.");
            }
        }
    }

    /**
     * Check if a track should be created for any of the filters
     *
     * @param \Gems\Agenda\AppointmentFilterInterface[] $filters
     * @param array $existingTracks
     * @param \Gems_Tracker $tracker
     *
     * @return int Number of tokenchanges
     */
    protected function checkCreateTracks($filters, $existingTracks, $tracker)
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
                /* @var $respTrack \Gems_Tracker_RespondentTrack */
                if (!$respTrack->hasSuccesCode()) { continue; }

                $createTrack = $this->$method($filter, $respTrack);
                if ($createTrack === false) {
                    break;  // Stop checking
                }
            }

            // \MUtil_Echo::track($trackId, $createTrack, $filter->getName(), $filter->getSqlAppointmentsWhere(), $filter->getFilterId());
            if ($createTrack) {
                $respTrack = $this->_createTrack($filter, $tracker);
                $existingTracks[$trackId][] = $respTrack;

                $tokenChanges += $respTrack->getCount();
            }
        }

        return $tokenChanges;
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
     * Has the track ended <wait days> ago?
     *
     * @param \Gems\Agenda\AppointmentFilterInterface $filter
     * @param \Gems_Tracker_RespondentTrack $respTrack
     *
     * @return boolean
     */
    public function createAfterWaitDays($filter, $respTrack)
    {
        $createTrack = true;
        $curr        = $this->getAdmissionTime();
        $end         = $respTrack->getEndDate();
        $wait        = $filter->getWaitDays();

        if ( (! $end) || ($curr->diffDays($end) <= $wait)) {
                $createTrack = false;
        }

        return $createTrack;
    }

    /**
     * Always report the track should be created
     *
     * @param \Gems\Agenda\AppointmentFilterInterface $filter
     * @param \Gems_Tracker_RespondentTrack $respTrack
     *
     * @return boolean
     */
    public function createAlways($filter, $respTrack)
    {
        $createTrack = $this->createAfterWaitDays($filter, $respTrack);

        if ($createTrack) {
            $createTrack = $this->createWhenNotInThisTrack($filter, $respTrack);
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
     * @param \Gems_Tracker_RespondentTrack $respTrack
     *
     * @return boolean
     */
    public function createNever()
    {
        return false;
    }

    /**
     * Create when current appointment is not assigned to this field already
     *
     * @param \Gems\Agenda\AppointmentFilterInterface $filter
     * @param \Gems_Tracker_RespondentTrack $respTrack
     *
     * @return boolean
     */
    public function createWhenNotInThisTrack($filter, $respTrack)
    {
        $createTrack = true;

        $data = $respTrack->getFieldData();
        if (isset($data[$filter->getFieldId()]) &&
                ($this->getId() == $data[$filter->getFieldId()])) {
            $createTrack = false;
        }

        return $createTrack;
    }

    /**
     * Only return true when no open track exists
     *
     * @param \Gems\Agenda\AppointmentFilterInterface $filter
     * @param \Gems_Tracker_RespondentTrack $respTrack
     *
     * @return boolean
     */
    public function createWhenNoOpen($filter, $respTrack)
    {
        // If an open track of this type exists: do not create a new one
        $createTrack = !$respTrack->isOpen();

        if ($createTrack) {
            $createTrack = $this->createAfterWaitDays($filter, $respTrack);
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
    public function getActivityDescription()
    {
        if (! (isset($this->_gemsData['gap_id_activity']) && $this->_gemsData['gap_id_activity'])) {
            $this->_gemsData['gaa_name'] = null;
        }
        if (!array_key_exists('gaa_name', $this->_gemsData)) {
            $sql = "SELECT gaa_name FROM gems__agenda_activities WHERE gaa_id_activity = ?";

            $this->_gemsData['gaa_name'] = $this->db->fetchOne($sql, $this->_gemsData['gap_id_activity']);

            // Cleanup db result
            if (false === $this->_gemsData['gaa_name']) {
                $this->_gemsData['gaa_name'] = null;
            }
        }
        return $this->_gemsData['gaa_name'];
    }

    /**
     * Return the id of the current activity
     *
     * @return int
     */
    public function getActivityId()
    {
        return $this->_gemsData['gap_id_activity'];
    }

    /**
     * Return the admission time
     *
     * @return \MUtil_Date Admission time as a date or null
     */
    public function getAdmissionTime()
    {
        if (isset($this->_gemsData['gap_admission_time']) && $this->_gemsData['gap_admission_time']) {
            if (! $this->_gemsData['gap_admission_time'] instanceof \MUtil_Date) {
                $this->_gemsData['gap_admission_time'] =
                        new \MUtil_Date($this->_gemsData['gap_admission_time'], \Gems_Tracker::DB_DATETIME_FORMAT);
            }
            // Clone to make sure calculations can be performed without changing this object
            return clone $this->_gemsData['gap_admission_time'];
        }
    }

    /**
     * Get the DB id of the attending by person
     *
     * @return int
     */
    public function getAttendedById()
    {
        return $this->_gemsData['gap_id_attended_by'];
    }

    /**
     * The cooment to the appointment
     *
     * @return string
     */
    public function getComment()
    {
        return $this->_gemsData['gap_comment'];
    }

    /**
     * Get method to call to check track creation
     *
     * @param int $type
     * @return string The method to call in this class
     */
    public function getCreatorCheckMethod($type)
    {
        static $methods = [
            0 => 'createNever',
            1 => 'createWhenNoOpen',
            2 => 'createAlways'
        ];

        // No checks, when type does not exists this is an error we want to be thrown
        return $methods[$type];
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
        $results[] = $this->getAdmissionTime()->toString($this->agenda->appointmentDisplayFormat);
        $results[] = $this->getActivityDescription();
        $results[] = $this->getProcedureDescription();
        $results[] = $this->getLocationDescription();
        $results[] = $this->getSubject();

        return implode($this->_('; '), array_filter($results));
    }

    /**
     *
     * @return \Gems|Agenda\EpisodeOfCare
     */
    public function getEpisode()
    {
        $episodeId = $this->getEpisodeId();

        if ($episodeId) {
            return $this->agenda->getEpisodeOfCare($episodeId);
        }
    }

    /**
     *
     * @return int
     */
    public function getEpisodeId()
    {
        return $this->_gemsData['gap_id_episode'];
    }

    /**
     * Return the appointment id
     *
     * @return int
     */
    public function getId()
    {
        return $this->_appointmentId;
    }

    /**
     * Return the description of the current location
     *
     * @return string or null when not found
     */
    public function getLocationDescription()
    {
        if (! (isset($this->_gemsData['gap_id_location']) && $this->_gemsData['gap_id_location'])) {
            $this->_gemsData['glo_name'] = null;
        }
        if (!array_key_exists('glo_name', $this->_gemsData)) {
            $sql = "SELECT glo_name FROM gems__locations WHERE glo_id_location = ?";

            $this->_gemsData['glo_name'] = $this->db->fetchOne($sql, $this->_gemsData['gap_id_location']);

            // Cleanup db result
            if (false === $this->_gemsData['glo_name']) {
                $this->_gemsData['glo_name'] = null;
            }
        }
        return $this->_gemsData['glo_name'];
    }

    /**
     * Get the DB id of the location
     *
     * @return int
     */
    public function getLocationId()
    {
        return $this->_gemsData['gap_id_location'];
    }

    /**
     *
     * @return int
     */
    public function getOrganizationId()
    {
        return $this->_gemsData['gap_id_organization'];
    }

    /**
     *
     * @return string The respondents patient number
     */
    public function getPatientNumber()
    {
        if (! isset($this->_gemsData['gr2o_patient_nr'])) {
            $this->_ensureRespondentOrgData();
        }

        return $this->_gemsData['gr2o_patient_nr'];
    }

    /**
     * Return the description of the current procedure
     *
     * @return string or null when not found
     */
    public function getProcedureDescription()
    {
        if (! (isset($this->_gemsData['gap_id_procedure']) && $this->_gemsData['gap_id_procedure'])) {
            return null;
        }
        if (!array_key_exists('gapr_name', $this->_gemsData)) {
            $sql = "SELECT gapr_name FROM gems__agenda_procedures WHERE gapr_id_procedure = ?";

            $this->_gemsData['gapr_name'] = $this->db->fetchOne($sql, $this->_gemsData['gap_id_procedure']);

            // Cleanup db result
            if (false === $this->_gemsData['gapr_name']) {
                $this->_gemsData['gapr_name'] = null;
            }
        }
        return $this->_gemsData['gapr_name'];
    }

    /**
     * Return the id of the current procedure
     *
     * @return int
     */
    public function getProcedureId()
    {
        return $this->_gemsData['gap_id_procedure'];
    }

    /**
     * Get the DB id of the referred by person
     *
     * @return int
     */
    public function getReferredById()
    {
        return $this->_gemsData['gap_id_referred_by'];
    }

    /**
     * Return the respondent object
     *
     * @return \Gems_Tracker_Respondent
     */
    public function getRespondent()
    {
        return $this->loader->getRespondent(
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
    public function getRespondentId()
    {
        return $this->_gemsData['gap_id_user'];
    }

    /**
     * The source of the appointment
     *
     * @return string
     */
    public function getSource()
    {
        return $this->_gemsData['gap_source'];
    }

    /**
     * The source id of the appointment
     *
     * @return string
     */
    public function getSourceId()
    {
        return $this->_gemsData['gap_id_in_source'];
    }

    /**
     * The subject of the appointment
     *
     * @return string
     */
    public function getSubject()
    {
        return isset($this->_gemsData['gap_subject']) ? $this->_gemsData['gap_subject'] : null;
    }

    /**
     *
     * @return boolean
     */
    public function hasEpisode()
    {
        return (boolean) $this->_gemsData['gap_id_episode'];
    }

    /**
     * Return true when the status is active
     *
     * @return type
     */
    public function isActive()
    {
        return $this->exists &&
                isset($this->_gemsData['gap_status']) &&
                $this->agenda->isStatusActive($this->_gemsData['gap_status']);
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
            $select->from('gems__appointments')
                    ->where('gap_id_appointment = ?', $this->_appointmentId);

            $this->_gemsData = $this->db->fetchRow($select);
            if (false == $this->_gemsData) {
                // on failure, reset to empty array
                $this->_gemsData = array();
            }
        }
        $this->exists = isset($this->_gemsData['gap_id_appointment']);

        if ($this->currentUser instanceof \Gems_User_User) {
            $this->_gemsData = $this->currentUser->applyGroupMask($this->_gemsData);
        }

        return $this;
    }

    /**
     * Recalculate all tracks that use this appointment
     *
     * @return int The number of tokens changed by this code
     */
    public function updateTracks()
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
                ->where('gr2t2a_id_appointment = ?', $this->_appointmentId)
                ->distinct();

        // AND find the filters for any new fields to fill
        $filters = $this->agenda->matchFilters($this);
        if ($filters) {
            $ids = array_map(function ($value) {
                return $value->getTrackId();
            }, $filters);

            // \MUtil_Echo::track(array_keys($filters), $ids);
            $respId = $this->getRespondentId();
            $orgId  = $this->getOrganizationId();
            $select->orWhere(
                    "gr2t_id_user = $respId AND gr2t_id_organization = $orgId AND gr2t_id_track IN (" .
                    implode(', ', $ids) . ")"
                    );
            // \MUtil_Echo::track($this->getId(), implode(', ', $ids));
        }

        // \MUtil_Echo::track($select->__toString());

        // Now find all the existing tracks that should be checked
        $respTracks = $this->db->fetchPairs($select);

        // \MUtil_Echo::track($respTracks);
        $existingTracks = array();
        if ($respTracks) {
            foreach ($respTracks as $respTrackId => $trackId) {
                $respTrack = $tracker->getRespondentTrack($respTrackId);

                // Recalculate this track
                $fieldsChanged = false;
                $tokenChanges += $respTrack->recalculateFields($fieldsChanged);

                // Store the track for creation checking
                $existingTracks[$trackId][] = $respTrack;
            }
        }

        // Only check if we need to create when this appointment is active and today or later
        if ($this->isActive() && $this->getAdmissionTime()->isLaterOrEqual(new \MUtil_Date())) {
            $tokenChanges += $this->checkCreateTracks($filters, $existingTracks, $tracker);
        }

        return $tokenChanges;
    }
}