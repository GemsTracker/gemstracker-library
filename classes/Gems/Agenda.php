<?php

/**
 *
 * @package    Gems
 * @subpackage Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @version    $Id: Afenda.php$
 */

use Gems\Agenda\AppointmentFilterInterface;
use Gems\Agenda\AppointmentSelect;
use Gems\Agenda\EpisodeOfCare;

/**
 *
 *
 * @package    Gems
 * @subpackage Agenda
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class Gems_Agenda extends \Gems_Loader_TargetLoaderAbstract
{
    /**
     *
     * @var \Gems_Agenda_Appointment[]
     */
    private $_appointments = array();

    /**
     *
     * @var AppointmentFilterInterface[]
     */
    private $_filters = array();

    /**
     *
     * @var string
     */
    public $appointmentDisplayFormat = 'dd-MM-yyyy HH:mm';

    /**
     *
     * @var string
     */
    public $episodeDisplayFormat = 'dd-MM-yyyy';

    /**
     *
     * @var \Zend_Cache_Core
     */
    protected $cache;

    /**
     * Allows sub classes of \Gems_Loader_LoaderAbstract to specify the subdirectory where to look for.
     *
     * @var string $cascade An optional subdirectory where this subclass always loads from.
     */
    protected $cascade = 'Agenda';

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var \Zend_Translate
     */
    protected $translate;

    /**
     *
     * @var \Zend_Translate_Adapter
     */
    protected $translateAdapter;

    /**
     *
     * @param type $container A container acting as source for \MUtil_Registry_Source
     * @param array $dirs The directories where to look for requested classes
     */
    public function __construct($container, array $dirs)
    {
        parent::__construct($container, $dirs);

        // Make sure the tracker is known
        $this->addRegistryContainer(array('agenda' => $this));
    }

    /**
     * Copy from \Zend_Translate_Adapter
     *
     * Translates the given string
     * returns the translation
     *
     * @param  string              $text   Translation string
     * @param  string|\Zend_Locale $locale (optional) Locale/Language to use, identical with locale
     *                                     identifier, @see \Zend_Locale for more information
     * @return string
     */
    public function _($text, $locale = null)
    {
        return $this->translateAdapter->_($text, $locale);
    }

    /**
     * Get the select statement for appointments in getAppointments()
     *
     * Allows for overruling on project level
     *
     * @return \Zend_Db_Select
     */
    protected function _getAppointmentSelect()
    {
        $select = $this->db->select();

        $select->from('gems__appointments')
                ->joinLeft( 'gems__agenda_activities', 'gap_id_activity = gaa_id_activity')
                ->joinLeft('gems__agenda_procedures',  'gap_id_procedure = gapr_id_procedure')
                ->joinLeft('gems__locations',          'gap_id_location = glo_id_location')
                ->order('gap_admission_time DESC');

        return $select;
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * This function is no needed if the classes are setup correctly
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        $this->initTranslateable();
    }

    /**
     * Add Activities to the table
     *
     * Override this method for other defaults
     *
     * @param string $name
     * @param int $organizationId
     * @return array
     */
    public function addActivity($name, $organizationId)
    {
        $model = new \MUtil_Model_TableModel('gems__agenda_activities');
        \Gems_Model::setChangeFieldsByPrefix($model, 'gaa');

        $values = array(
            'gaa_name'            => $name,
            'gaa_id_organization' => $organizationId,
            'gaa_match_to'        => $name,
            'gaa_active'          => 1,
            'gaa_filter'          => 0,
        );

        $result = $model->save($values);

        $this->cache->clean(\Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, array('activity', 'activities'));

        return $result;
    }

    /**
     * Add HealthcareStaff to the table
     *
     * Override this method for other defaults
     *
     * @param string $name
     * @param int $organizationId
     * @return array
     */
    public function addHealthcareStaff($name, $organizationId)
    {
        $model = new \MUtil_Model_TableModel('gems__agenda_staff');
        \Gems_Model::setChangeFieldsByPrefix($model, 'gas');

        $values = array(
            'gas_name'            => $name,
            'gas_id_organization' => $organizationId,
            'gas_match_to'        => $name,
            'gas_active'          => 1,
            'gas_filter'          => 0,
        );

        $result = $model->save($values);

        $this->cache->clean(\Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, array('staff'));

        return $result;
    }

    /**
     *
     * @param type $name
     * @param type $organizationId
     * @param array $matches
     * @return array
     */
    public function addLocation($name, $organizationId, $matches)
    {
        if (empty($matches)) {
            // A new match
            $values = array(
                'glo_name'          => $name,
                'glo_organizations' => ':' . $organizationId . ':',
                'glo_match_to'      => $name,
                'glo_active'        => 1,
                'glo_filter'        => 0,
            );
        } else {
            // Update
            $first = reset($matches);

            // Change this match, add this organization
            $values = array(
                'glo_id_location'   => $first['glo_id_location'],
                'glo_organizations' => ':' . implode(':', array_keys($matches)) . ':' .
                    $organizationId . ':'
            );
        }

        $model = new \MUtil_Model_TableModel('gems__locations');
        \Gems_Model::setChangeFieldsByPrefix($model, 'glo');

        $result = $model->save($values);

        $this->cache->clean(\Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, array('location', 'locations'));

        return $result;
    }

    /**
     * Add Procedure to the table
     *
     * Override this method for other defaults
     *
     * @param string $name
     * @param int $organizationId
     * @return array
     */
    public function addProcedure($name, $organizationId)
    {
        $model = new \MUtil_Model_TableModel('gems__agenda_procedures');
        \Gems_Model::setChangeFieldsByPrefix($model, 'gapr');

        $values = array(
            'gapr_name'            => $name,
            'gapr_id_organization' => $organizationId,
            'gapr_match_to'        => $name,
            'gapr_active'          => 1,
            'gapr_filter'          => 0,
        );

        $result = $model->save($values);

        $this->cache->clean(\Zend_Cache::CLEANING_MODE_MATCHING_ANY_TAG, array('procedure', 'procedures'));

        return $result;
    }

    /**
     * Dynamically load and create a [Gems|Project]_Agenda_ class
     *
     * @param string $className
     * @param mixed $param1
     * @param mixed $param2
     * @return object
     */
    public function createAgendaClass($className, $param1 = null, $param2 = null)
    {
        $params = func_get_args();
        array_shift($params);

        return $this->_loadClass($className, true, $params);
    }

    /**
     * Create agenda select
     *
     * @param string|array $fields The appointment fields to select
     * @return \Gems\Agenda\AppointmentSelect
     */
    public function createAppointmentSelect($fields = '*')
    {
        return $this->_loadClass('AppointmentSelect', true, array($fields));
    }

    /**
     * Get all active respondents for this user
     *
     * @param int $respondentId When null $patientNr is required
     * @param int $organizationId
     * @param string $patientNr Optional for when $respondentId is null
     * @param string $where Optional extra where statement
     * @return array appointmentId => appointment description
     */
    public function getActiveAppointments($respondentId, $organizationId, $patientNr = null, $where = null)
    {
        if ($where) {
            $where = "($where) AND ";
        } else {
            $where = "";
        }
        $where .= sprintf('gap_status IN (%s)', $this->getStatusKeysActiveDbQuoted());

        return $this->getAppointments($respondentId, $organizationId, $patientNr, $where);
    }

    /**
     *
     * @param int $organizationId Optional
     * @return array activity_id => name
     */
    public function getActivities($organizationId = null)
    {
        $cacheId = __CLASS__ . '_' . __FUNCTION__ . '_' . $organizationId;

        if ($results = $this->cache->load($cacheId)) {
            return $results;
        }

        $select = $this->db->select();
        $select->from('gems__agenda_activities', array('gaa_id_activity', 'gaa_name'))
                ->order('gaa_name');

        if ($organizationId) {
            // Check only for active when with $orgId: those are usually used
            // with editing, while the whole list is used for display.
            $select->where('gaa_active = 1')
                    ->where('(
                            gaa_id_organization IS NULL
                        AND
                            gaa_name NOT IN (SELECT gaa_name FROM gems__agenda_activities WHERE gaa_id_organization = ?)
                        ) OR
                            gaa_id_organization = ?', $organizationId);
        }
        // \MUtil_Echo::track($select->__toString());
        $results = $this->db->fetchPairs($select);
        $this->cache->save($results, $cacheId, array('activities'));
        return $results;

    }

    /**
     * Overrule this function to adapt the display of the agenda items for each project
     *
     * @see \Gems_Agenda_Appointment->getDisplayString()
     *
     * @param array $row Row containing result select
     * @return string
     */
    public function getAppointmentDisplay(array $row)
    {
        $date = new \MUtil_Date($row['gap_admission_time'], 'yyyy-MM-dd HH:mm:ss');
        $results[] = $date->toString($this->appointmentDisplayFormat);
        if ($row['gaa_name']) {
            $results[] = $row['gaa_name'];
        }
        if ($row['gapr_name']) {
            $results[] = $row['gapr_name'];
        }
        if ($row['glo_name']) {
            $results[] = $row['glo_name'];
        }

        return implode($this->_('; '), $results);
    }

    /**
     * Get an appointment object
     *
     * @param mixed $appointmentData Appointment id or array containing appointment data
     * @return \Gems_Agenda_Appointment
     */
    public function getAppointment($appointmentData)
    {
        if (! $appointmentData) {
            throw new \Gems_Exception_Coding('Provide at least the apppointment id when requesting an appointment.');
        }

        if (is_array($appointmentData)) {
             if (!isset($appointmentData['gap_id_appointment'])) {
                 throw new \Gems_Exception_Coding(
                         '$appointmentData array should atleast have a key "gap_id_appointment" containing the requested appointment id'
                         );
             }
            $appointmentId = $appointmentData['gap_id_appointment'];
        } else {
            $appointmentId = $appointmentData;
        }
        // \MUtil_Echo::track($appointmentId, $appointmentData);

        if (! isset($this->_appointments[$appointmentId])) {
            $this->_appointments[$appointmentId] = $this->_loadClass('appointment', true, array($appointmentData));
        } elseif (is_array($appointmentData)) {
            // Make sure the new values are set in the object
            $this->_appointments[$appointmentId]->refresh($appointmentData);
        }

        return $this->_appointments[$appointmentId];
    }

    /**
     * Get all appointments for a respondent
     *
     * @param int $respondentId When null $patientNr is required
     * @param int $organizationId
     * @param string $patientNr Optional for when $respondentId is null
     * @param string $where Optional extra where statement
     * @return array appointmentId => appointment description
     */
    public function getAppointments($respondentId, $organizationId, $patientNr = null, $where = null)
    {
        $select = $this->_getAppointmentSelect();

        if ($where) {
            $select->where($where);
        }

        if ($respondentId) {
            $select->where('gap_id_user = ?', $respondentId)
                    ->where('gap_id_organization = ?', $organizationId);
        } else {
            // Join might have been created in _getAppointmentSelect
            $from = $select->getPart(\Zend_Db_Select::FROM);
            if (! isset($from['gems__respondent2org'])) {
                $select->joinInner(
                        'gems__respondent2org',
                        'gap_id_user = gr2o_id_user AND gap_id_organization = gr2o_id_organization',
                        array()
                        );
            }

            $select->where('gr2o_patient_nr = ?', $patientNr)
                    ->where('gr2o_id_organization = ?', $organizationId);
        }

        // \MUtil_Echo::track($select->__toString());
        $rows = $this->db->fetchAll($select);

        if (! $rows) {
            return array();
        }

        $results = array();
        foreach ($rows as $row) {
            $results[$row['gap_id_appointment']] = $this->getAppointmentDisplay($row);
        }
        return $results;
    }

    /**
     * Get all appointments for an episode
     *
     * @param int|Episode $episode Episode Id or object
     * @return array appointmentId => appointment object
     */
    public function getAppointmentsForEpisode($episode)
    {
        $select = $this->_getAppointmentSelect();

        if ($episode instanceof EpisodeOfCare) {
            $episodeId = $episode->getId();
        } else {
            $episodeId = $episode;
        }
        $select->where('gap_id_episode = ?', $episodeId);

        // \MUtil_Echo::track($select->__toString());
        $rows = $this->db->fetchAll($select);

        if (! $rows) {
            return array();
        }

        $results = array();
        foreach ($rows as $row) {
            $results[$row['gap_id_appointment']] = $this->getAppointment($row);
        }
        return $results;
    }

    /**
     * Get a selection list of coding systems used
     *
     * @return array code => label
     */
    public function getDiagnosisCodingSystems()
    {
        return [
            'DBC'    => $this->_('DBC (Dutch Diagnosis Treatment Code)'),
            'manual' => $this->_('Manual (organization specific)'),
        ];
    }

    /**
     * Get an appointment object
     *
     * @param mixed $episodeData Episode id or array containing episode data
     * @return \Gems\Agenda\EpisodeOfCare
     */
    public function getEpisodeOfCare($episodeData)
    {
        if (! $episodeData) {
            throw new \Gems_Exception_Coding('Provide at least the episode id when requesting an episode of care.');
        }

        if (is_array($episodeData)) {
             if (!isset($episodeData['gec_episode_of_care_id'])) {
                 throw new \Gems_Exception_Coding(
                         '$episodeData array should atleast have a key "gec_episode_of_care_id" containing the requested episode id'
                         );
             }
        }
        // \MUtil_Echo::track($appointmentId, $appointmentData);

        return $this->_loadClass('episodeOfCare', true, array($episodeData));
    }

    /**
     * Get the status codes for all episode of care  items
     *
     * @return array code => label
     */
    public function getEpisodeStatusCodes()
    {
        $codes = $this->getEpisodeStatusCodesActive() +
                $this->getEpisodeStatusCodesInactive();

        asort($codes);

        return $codes;
    }

    /**
     * Get the status codes for active episode of care items
     *
     * see https://www.hl7.org/fhir/episodeofcare.html
     *
     * @return array code => label
     */
    public function getEpisodeStatusCodesActive()
    {
        // A => active, C => Cancelled, E => Error, F => Finished, O => Onhold, P => Planned, W => Waitlist
        $codes = array(
            'A' => $this->_('Active'),
            'F' => $this->_('Finished'),
            'O' => $this->_('On hold'),
            'P' => $this->_('Planned'),
            'W' => $this->_('Waitlist'),
        );

        asort($codes);

        return $codes;
    }

    /**
     * Get the status codes for inactive episode of care items
     *
     * @return array code => label
     */
    public function getEpisodeStatusCodesInactive()
    {
        $codes = array(
            'C' => $this->_('Cancelled'),
            'E' => $this->_('Erroneous input'),
        );

        asort($codes);

        return $codes;
    }

    /**
     * Get the options list episodes for episodes
     *
     * @param array $episodes
     * @return array of $episodeId => Description
     */
    public function getEpisodesAsOptions(array $episodes)
    {
        $options = [];

        foreach ($episodes as $id => $episode) {
            if ($episode instanceof EpisodeOfCare) {
                $options[$id] = $episode->getDisplayString();
            }
        }

        return $options;
    }

    /**
     * Get the episodes for a respondent
     *
     * @param \Gems_Tracker_Respondent $respondent
     * @param $where mixed Optional extra string or array filter
     * @return array of $episodeId => \Gems\Agenda\EpisodeOfCare
     */
    public function getEpisodesFor(\Gems_Tracker_Respondent $respondent, $where = null)
    {
        return $this->getEpisodesForRespId($respondent->getId(), $respondent->getOrganizationId(), $where);
    }

    /**
     * Get the episodes for a respondent
     *
     * @param int $respondentId
     * @param int $orgId
     * @param $where mixed Optional extra string or array filter
     * @return array of $episodeId => \Gems\Agenda\EpisodeOfCare
     */
    public function getEpisodesForRespId($respondentId, $orgId, $where = null)
    {
        $select = $this->db->select();
        $select->from('gems__episodes_of_care')
                ->where('gec_id_user = ?', $respondentId)
                ->where('gec_id_organization = ?', $orgId)
                ->order('gec_startdate DESC');

        if ($where) {
            if (is_array($where)) {
                foreach ($where as $expr => $param) {
                    if (is_int($expr)) {
                        $select->where($param);
                    } else {
                        $select->where($expr, $param);
                    }
                }
            } else {
                $select->where($where);
            }
        }
        // \MUtil_Echo::track($select->__toString());

        $episodes = $this->db->fetchAll($select);
        $output   = [];

        foreach ($episodes as $episodeData) {
            $episode = $this->getEpisodeOfCare($episodeData);
            $output[$episode->getId()] = $episode;
        }

        return $output;
    }

    /**
     * Load the list of assignable filters
     *
     * @return array filter_id => label
     */
    public function getFilterList()
    {
        $cacheId = __CLASS__ . '_' . __FUNCTION__;

        $output = $this->cache->load($cacheId);
        if ($output) {
            return $output;
        }

        $output = $this->db->fetchPairs("SELECT gaf_id, COALESCE(gaf_manual_name, gaf_calc_name) "
                . "FROM gems__appointment_filters WHERE gaf_active = 1 ORDER BY gaf_id_order");

        $this->cache->save($output, $cacheId, array('appointment_filters'));

        return $output;
    }

    /**
     * Get the field names in appontments with their labels as the value
     *
     * @return array fieldname => label
     */
    public final function getFieldLabels()
    {
        $output = \Mutil_Ra::column('label', $this->getFieldData());

        asort($output);

        return $output;
    }

    /**
     * Get a structured nested array contain information on all the appointment
     *
     * @return array fieldname => array(label[, tableName, tableId, tableLikeFilter))
     */
    protected function getFieldData()
    {
        return array(
            'gap_id_organization' => array(
                'label' => $this->_('Organization'),
                'tableName' => 'gems__organizations',
                'tableId' => 'gor_id_organization',
                'tableLikeFilter' => "gor_active = 1 AND gor_name LIKE '%s'",
                ),
            'gap_source' => array(
                'label' => $this->_('Source of appointment'),
                ),
            'gap_id_attended_by' => array(
                'label' => $this->_('With'),
                'tableName' => 'gems__agenda_staff',
                'tableId' => 'gas_id_staff',
                'tableLikeFilter' => "gas_active = 1 AND gas_name LIKE '%s'",
                ),
            'gap_id_referred_by' => array(
                'label' => $this->_('Referrer'),
                'tableName' => 'gems__agenda_staff',
                'tableId' => 'gas_id_staff',
                'tableLikeFilter' => "gas_active = 1 AND gas_name LIKE '%s'",
                ),
            'gap_id_activity' => array(
                'label' => $this->_('Activity'),
                'tableName' => 'gems__agenda_activities',
                'tableId' => 'gaa_id_activity',
                'tableLikeFilter' => "gaa_active = 1 AND gaa_name LIKE '%s'",
                ),
            'gap_id_procedure' => array(
                'label' => $this->_('Procedure'),
                'tableName' => 'gems__agenda_procedures',
                'tableId' => 'gapr_id_procedure',
                'tableLikeFilter' => "gapr_active = 1 AND gapr_name LIKE '%s'",
                ),
            'gap_id_location' => array(
                'label' => $this->_('Location'),
                'tableName' => 'gems__locations',
                'tableId' => 'glo_id_location',
                'tableLikeFilter' => "glo_active = 1 AND glo_name LIKE '%s'",
                ),
            'gap_subject' => array(
                'label' => $this->_('Subject'),
                ),
        );
    }

    /**
     * Get a filter from the database
     *
     * @param $filterId Id of a single filter
     * @return AppointmentFilterInterface or null
     */
    public function getFilter($filterId)
    {
        static $filters = array();

        if (isset($filters[$filterId])) {
            return $filters[$filterId];
        }
        $found = $this->getFilters("SELECT *
                FROM gems__appointment_filters LEFT JOIN gems__track_appointments ON gaf_id = gtap_filter_id
                WHERE gaf_active = 1 AND gaf_id = $filterId LIMIT 1");

        if ($found) {
            $filters[$filterId] = reset($found);
            return $filters[$filterId];
        }
    }

    /**
     * Get the filters from the database
     *
     * @param $sql SQL statement
     * @return AppointmentFilterInterface[]
     */
    public function getFilters($sql)
    {
        $classes    = array();
        $filterRows = $this->db->fetchAll($sql);
        $output     = array();

        // \MUtil_Echo::track($filterRows);
        foreach ($filterRows as $key => $filter) {
            $className = $filter['gaf_class'];
            if (! isset($classes[$className])) {
                $classes[$className] = $this->newFilterObject($className);
            }
            $filterObject = clone $classes[$className];
            if ($filterObject instanceof AppointmentFilterInterface) {
                $filterObject->exchangeArray($filter);
                $output[$key] = $filterObject;
            }
        }
        // \MUtil_Echo::track(count($filterRows), count($output));

        return $output;
    }

    /**
     *
     * @param int $organizationId Optional
     * @return array activity_id => name
     */
    public function getHealthcareStaff($organizationId = null)
    {
        $cacheId = __CLASS__ . '_' . __FUNCTION__ . '_' . $organizationId;

        if ($results = $this->cache->load($cacheId)) {
            return $results;
        }

        $select = $this->db->select();
        $select->from('gems__agenda_staff', array('gas_id_staff', 'gas_name'))
                ->order('gas_name');

        if ($organizationId) {
            // Check only for active when with $orgId: those are usually used
            // with editing, while the whole list is used for display.
            $select->where('gas_active = 1')
                    ->where('gas_id_organization = ?', $organizationId);
        }
        // \MUtil_Echo::track($select->__toString());
        $results = $this->db->fetchPairs($select);
        $this->cache->save($results, $cacheId, array('staff'));
        return $results;

    }

    /**
     * Returns an array with identical key => value pairs containing care provision locations.
     *
     * @param int $orgId Optional to slect for single organization
     * @return array
     */
    public function getLocations($orgId = null)
    {
        // Make sure no invalid data gets through
        $orgId = intval($orgId);

        $cacheId = __CLASS__ . '_' . __FUNCTION__ . '_' . $orgId;

        if ($results = $this->cache->load($cacheId)) {
            return $results;
        }

        $select = $this->db->select();
        $select->from('gems__locations', array('glo_id_location', 'glo_name'))
                ->order('glo_name');

        if ($orgId) {
            // Check only for active when with $orgId: those are usually used
            // with editing, while the whole list is used for display.
            $select->where('glo_active = 1');
            $select->where("glo_organizations LIKE '%:$orgId:%'");
        }

        $results = $this->db->fetchPairs($select);
        $this->cache->save($results, $cacheId, array('locations'));
        return $results;
    }


    /**
     *
     * @param int $organizationId Optional
     * @return array activity_id => name
     */
    public function getProcedures($organizationId = null)
    {
        $cacheId = __CLASS__ . '_' . __FUNCTION__ . '_' . $organizationId;

        if ($results = $this->cache->load($cacheId)) {
            return $results;
        }

        $select = $this->db->select();
        $select->from('gems__agenda_procedures', array('gapr_id_procedure', 'gapr_name'))
                ->order('gapr_name');

        if ($organizationId) {
            // Check only for active when with $orgId: those are usually used
            // with editing, while the whole list is used for display.
            $select->where('gapr_active = 1')
                    ->where('(
                            gapr_id_organization IS NULL
                        AND
                            gapr_name NOT IN (SELECT gapr_name FROM gems__agenda_procedures WHERE gapr_id_procedure = ?)
                        ) OR
                            gapr_id_organization = ?', $organizationId);
        }
        // \MUtil_Echo::track($select->__toString());
        $results = $this->db->fetchPairs($select);
        $this->cache->save($results, $cacheId, array('procedures'));
        return $results;
    }

    /**
     * Get the status codes for all active agenda items
     *
     * @return array code => label
     */
    public function getStatusCodes()
    {
        $codes = $this->getStatusCodesActive() +
                $this->getStatusCodesInactive();

        asort($codes);

        return $codes;
    }

    /**
     * Get the status codes for active agenda items
     *
     * @return array code => label
     */
    public function getStatusCodesActive()
    {
        $codes = array(
            'AC' => $this->_('Active appointment'),
            'CO' => $this->_('Completed appointment'),
        );

        asort($codes);

        return $codes;
    }

    /**
     * Get the status codes for inactive agenda items
     *
     * @return array code => label
     */
    public function getStatusCodesInactive()
    {
        $codes = array(
            'AB' => $this->_('Aborted appointment'),
            'CA' => $this->_('Cancelled appointment'),
        );

        asort($codes);

        return $codes;
    }

    /**
     * Get the status keys for active agenda items
     *
     * @return array nr => code
     */
    public function getStatusKeysActive()
    {
        return array_keys($this->getStatusCodesActive());
    }

    /**
     * Get the status keys for active agenda items as a quoted db query string for use in "x IN (?)"
     *
     * @return \Zend_Db_Expr
     */
    public function getStatusKeysActiveDbQuoted()
    {
        $codes = array();
        foreach ($this->getStatusKeysActive() as $key) {
            $codes[] = $this->db->quote($key);
        }
        return new \Zend_Db_Expr(implode(", ", $codes));
    }

    /**
     * Get the status keys for inactive agenda items
     *
     * @return array nr => code
     */
    public function getStatusKeysInactive()
    {
        return array_keys($this->getStatusCodesInactive());
    }

    /**
     * Get the status keys for active agenda items as a quoted db query string for use in "x IN (?)"
     *
     * @return \Zend_Db_Expr
     */
    public function getStatusKeysInactiveDbQuoted()
    {
        $codes = array();
        foreach ($this->getStatusKeysInactive() as $key) {
            $codes[] = $this->db->quote($key);
        }
        return new \Zend_Db_Expr(implode(", ", $codes));
    }

    /**
     * Get the element that allows to create a track from an appointment
     *
     * When adding a new type, make sure to modify \Gems_Agenda_Appointment too
     * @see \Gems_Agenda_Appointment::getCreatorCheckMethod()
     *
     * @return array
     */
    public function getTrackCreateElement()
    {
        return array(
                'elementClass' => 'Radio',
                'multiOptions' => [
                    0 => $this->_('Never'),
                    1 => $this->_('When no open track exists'),
                    2 => $this->_('Always')
                    ],
                'label'        => $this->_('Create track'),
                'onclick'      => 'this.form.submit();',
                );
    }

    /**
     * Get the type codes for agenda items
     *
     * @return array code => label
     */
    public function getTypeCodes()
    {
        return array(
            'A' => $this->_('Ambulatory'),
            'E' => $this->_('Emergency'),
            'F' => $this->_('Field'),
            'H' => $this->_('Home'),
            'I' => $this->_('Inpatient'),
            'S' => $this->_('Short stay'),
            'V' => $this->_('Virtual'),
        );
    }

    /**
     * Function that checks the setup of this class/traight
     *
     * This function is not needed if the variables have been defined correctly in the
     * source for this object and theose variables have been applied.
     *
     * return @void
     */
    protected function initTranslateable()
    {
        if ($this->translateAdapter instanceof \Zend_Translate_Adapter) {
            // OK
            return;
        }

        if ($this->translate instanceof \Zend_Translate) {
            // Just one step
            $this->translateAdapter = $this->translate->getAdapter();
            return;
        }

        if ($this->translate instanceof \Zend_Translate_Adapter) {
            // It does happen and if it is all we have
            $this->translateAdapter = $this->translate;
            return;
        }

        // Make sure there always is an adapter, even if it is fake.
        $this->translateAdapter = new \MUtil_Translate_Adapter_Potemkin();
    }

    /**
     * Returns true when the status code is active
     *
     * @param string $code
     * @return boolean
     */
    public function isStatusActive($code)
    {
        $stati = $this->getStatusCodesActive();

        return isset($stati[$code]);
    }

    /**
     * Load the filters from cache or elsewhere
     *
     * @return AppointmentFilterInterface[]
     */
    protected function loadDefaultFilters()
    {
        if ($this->_filters) {
            return $this->_filters;
        }

        $cacheId = __CLASS__ . '_' . __FUNCTION__;

        $output = $this->cache->load($cacheId);
        if ($output) {
            foreach ($output as $key => $filterObject) {
                // Filterobjects should not serialize anything loaded from a source
                if ($filterObject instanceof \MUtil_Registry_TargetInterface) {
                    $this->applySource($filterObject);
                }
                $this->_filters[$key] = $filterObject;
            }
            return $this->_filters;
        }

        $this->_filters = $this->getFilters("SELECT *
                FROM gems__appointment_filters INNER JOIN
                    gems__track_appointments ON gaf_id = gtap_filter_id INNER JOIN
                    gems__tracks ON gtap_id_track = gtr_id_track
                WHERE gaf_active = 1 AND gtr_active = 1 AND gtr_date_start <= CURRENT_DATE AND
                    (gtr_date_until IS NULL OR gtr_date_until >= CURRENT_DATE)
                ORDER BY gaf_id_order, gtap_id_order");

        $this->cache->save($this->_filters, $cacheId, array('appointment_filters', 'tracks'));

        return $this->_filters;
    }

    /**
     * Find an activity code for the name and organization.
     *
     * @param string $name The name to match against
     * @param int $organizationId Organization id
     * @param boolean $create Create a match when it does not exist
     * @return int or null
     */
    public function matchActivity($name, $organizationId, $create = true)
    {
        $cacheId = __CLASS__ . '_' . __FUNCTION__;
        $matches = $this->cache->load($cacheId);

        if (! $matches) {
            $matches = array();
            $select  = $this->db->select();
            $select->from('gems__agenda_activities', array(
                'gaa_id_activity', 'gaa_match_to', 'gaa_id_organization', 'gaa_filter',
                ));

            $result = $this->db->fetchAll($select);
            foreach ($result as $row) {
                if (null === $row['gaa_id_organization']) {
                    $key = 'null';
                } else {
                    $key = $row['gaa_id_organization'];
                }
                foreach (explode('|', $row['gaa_match_to']) as $match) {
                    $matches[$match][$key] = $row['gaa_filter'] ? false : $row['gaa_id_activity'];
                }
            }
            $this->cache->save($matches, $cacheId, array('activities'));
        }

        if (isset($matches[$name])) {
            if (isset($matches[$name][$organizationId])) {
                return $matches[$name][$organizationId];
            }
            if (isset($matches[$name]['null'])) {
                return $matches[$name]['null'];
            }
        }

        if (! $create) {
            return null;
        }

        $result = $this->addActivity($name, $organizationId);

        return $result['gaa_filter'] ? false : $result['gaa_id_activity'];
    }

    /**
     *
     * @param mixed $to \Gems_Agenda_Appointment:EpsiodeOfCare
     * @return AppointmentFilterInterface[]
     */
    public function matchFilters($to)
    {
        $filters = $this->loadDefaultFilters();
        $output  = array();

        if ($to instanceof \Gems_Agenda_Appointment) {
            foreach ($filters as $filter) {
                if ($filter instanceof AppointmentFilterInterface) {
                    if ($filter->matchAppointment($to)) {
                        $output[] = $filter;
                    }
                }
            }
        } elseif ($to instanceof EpisodeOfCare) {
            foreach ($filters as $filter) {
                if ($filter instanceof AppointmentFilterInterface) {
                    if ($filter->matchEpisode($to)) {
                        $output[] = $filter;
                    }
                }
            }
        } else {
            throw new \Gems_Coding_Exception('The $to paramater must be either an appointment or an episode object.');
        }

        return $output;
    }

    /**
     * Find a healt care provider for the name and organization.
     *
     * @param string $name The name to match against
     * @param int $organizationId Organization id
     * @param boolean $create Create a match when it does not exist
     * @return int gas_id_staff staff id
     */
    public function matchHealthcareStaff($name, $organizationId, $create = true)
    {
        $cacheId = __CLASS__ . '_' . __FUNCTION__;
        $matches = $this->cache->load($cacheId);

        if (! $matches) {
            $matches = array();
            $select     = $this->db->select();
            $select->from('gems__agenda_staff')
                    ->order('gas_name');

            $result = $this->db->fetchAll($select);
            foreach ($result as $row) {
                foreach (explode('|', $row['gas_match_to']) as $match) {
                    $matches[$match][$row['gas_id_organization']] = $row['gas_filter'] ? false : $row['gas_id_staff'];
                }
            }
            $this->cache->save($matches, $cacheId, array('staff'));
        }

        if (isset($matches[$name])) {
            if ($organizationId) {
                if (isset($matches[$name][$organizationId])) {
                    return $matches[$name][$organizationId];
                }
            } else {
                // Return the first location among the organizations
                return reset($matches[$name]);
            }
        }

        if (! $create) {
            return null;
        }

        $result = $this->addHealthcareStaff($name, $organizationId);

        return $result['gas_filter'] ? false : $result['gas_id_staff'];
    }

    /**
     * Find a location for the name and organization.
     *
     * @param string $name The name to match against
     * @param int $organizationId Organization id
     * @param boolean $create Create a match when it does not exist
     * @return array location
     */
    public function matchLocation($name, $organizationId, $create = true)
    {
        $cacheId = __CLASS__ . '_' . __FUNCTION__;
        $matches = $this->cache->load($cacheId);

        if (! $matches) {
            $matches = array();
            $select     = $this->db->select();
            $select->from('gems__locations')
                    ->order('glo_name');

            $result = $this->db->fetchAll($select);
            foreach ($result as $row) {
                foreach (explode('|', $row['glo_match_to']) as $match) {
                    foreach (explode(':', trim($row['glo_organizations'], ':')) as $subOrg) {
                        $matches[$match][$subOrg] = $row;
                    }
                }
            }
            $this->cache->save($matches, $cacheId, array('locations'));
        }

        if (isset($matches[$name])) {
            if ($organizationId) {
                if (isset($matches[$name][$organizationId])) {
                    return $matches[$name][$organizationId];
                }
                // Not in this organization, if we create we update the record
            } else {
                // Return the first location among the organizations
                return reset($matches[$name]);
            }
        } else {
            $matches[$name] = null;
        }

        if (! $create) {
            return null;
        }

        $result = $this->addLocation($name, $organizationId, $matches[$name]);

        return $result;
    }

    /**
     * Find a procedure code for the name and organization.
     *
     * @param string $name The name to match against
     * @param int $organizationId Organization id
     * @param boolean $create Create a match when it does not exist
     * @return int or null
     */
    public function matchProcedure($name, $organizationId, $create = true)
    {
        $cacheId = __CLASS__ . '_' . __FUNCTION__;
        $matches = $this->cache->load($cacheId);

        if (! $matches) {
            $matches = array();
            $select  = $this->db->select();
            $select->from('gems__agenda_procedures', array(
                'gapr_id_procedure', 'gapr_match_to', 'gapr_id_organization', 'gapr_filter',
                ));

            $result = $this->db->fetchAll($select);
            foreach ($result as $row) {
                if (null === $row['gapr_id_organization']) {
                    $key = 'null';
                } else {
                    $key = $row['gapr_id_organization'];
                }
                foreach (explode('|', $row['gapr_match_to']) as $match) {
                    $matches[$match][$key] = $row['gapr_filter'] ? false : $row['gapr_id_procedure'];
                }
            }
            $this->cache->save($matches, $cacheId, array('procedures'));
        }

        if (isset($matches[$name])) {
            if (isset($matches[$name][$organizationId])) {
                return $matches[$name][$organizationId];
            }
            if (isset($matches[$name]['null'])) {
                return $matches[$name]['null'];
            }
        }

        if (! $create) {
            return null;
        }

        $result = $this->addProcedure($name, $organizationId);

        return $result['gapr_filter'] ? false : $result['gapr_id_procedure'];
    }

    /**
     * Creates a new filter class object
     *
     * @param string $className The part after *_Agenda_Filter_
     * @return object
     */
    public function newFilterObject($className)
    {
        return $this->_loadClass("Filter\\$className", true);
    }

    /**
     *
     * @return \Gems\Agenda\AppointmentFilterModel
     */
    public function newFilterModel()
    {
        return $this->_loadClass('AppointmentFilterModel', true);
    }

    /**
     * Copy from \Zend_Translate_Adapter
     *
     * Translates the given string using plural notations
     * Returns the translated string
     *
     * @see \Zend_Locale
     * @param  string              $singular Singular translation string
     * @param  string              $plural   Plural translation string
     * @param  integer             $number   Number for detecting the correct plural
     * @param  string|\Zend_Locale $locale   (Optional) Locale/Language to use, identical with
     *                                       locale identifier, @see \Zend_Locale for more information
     * @return string
     */
    public function plural($singular, $plural, $number, $locale = null)
    {
        $args = func_get_args();
        return call_user_func_array(array($this->translateAdapter, 'plural'), $args);
    }

    /**
     * Reset internally held data for testing
     *
     * @return $this
     */
    public function reset()
    {
        $this->_appointments = [];
        $this->_filters = [];

        return $this;
    }
}
