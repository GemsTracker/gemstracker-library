<?php

/**
 *
 * @package    Gems
 * @subpackage Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id: AppointmentFilterAbstract.php $
 */

namespace Gems\Agenda;

use Gems\Tracker\Engine\FieldsDefinition;
use Gems\Tracker\Model\FieldMaintenanceModel;

/**
 *
 *
 * @package    Gems
 * @subpackage Agenda
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 13-okt-2014 20:13:01
 */
abstract class BasicFilterAbstract extends \MUtil_Translate_TranslateableAbstract
    implements AppointmentFilterInterface, \Serializable
{
    /**
     * Constant for filters that should always trigger
     */
    const MATCH_ALL_SQL = '1=1';

    /**
     * Constant for filters that should never trigger
     */
    const NO_MATCH_SQL = '1=0';

    /**
     * Initial data settings
     *
     * @var array
     */
    protected $_data;

    /**
     * Override this function when you need to perform any actions when the data is loaded.
     *
     * Test for the availability of variables as these objects can be loaded data first after
     * deserialization or registry variables first after normal instantiation.
     *
     * That is why this function called both at the end of afterRegistry() and after exchangeArray(),
     * but NOT after unserialize().
     *
     * After this the object should be ready for serialization
     */
    protected function afterLoad()
    { }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        $this->afterLoad();
    }

    /**
     * Load the object from a data array
     *
     * @param array $data
     */
    public function exchangeArray(array $data)
    {
        $this->_data = $data;
        $this->afterLoad();
    }

    /**
     * Return the type of track creator this filter is
     *
     * @return int
     */
    public function getCreatorType()
    {
        return $this->_data['gtap_create_track'];
    }

    /**
     * The field id as it is recognized be the track engine
     *
     * @return string
     */
    public function getFieldId()
    {
        if (isset($this->_data['gtap_id_app_field']) && $this->_data['gtap_id_app_field']) {
            return FieldsDefinition::makeKey(
                    FieldMaintenanceModel::APPOINTMENTS_NAME,
                    $this->_data['gtap_id_app_field']
                    );
        }
    }

    /**
     * The filter id
     *
     * @return int
     */
    public function getFilterId()
    {
        return $this->_data['gaf_id'];
    }

    /**
     * The name of the filter
     *
     * @return string
     */
    public function getName()
    {
        return $this->_data['gaf_manual_name'] ? $this->_data['gaf_manual_name'] : $this->_data['gaf_calc_name'];
    }

    /**
     * Generate a where statement to filter an appointment model
     *
     * @return string
     */
    // public function getSqlAppointmentsWhere();

    /**
     * Generate a where statement to filter an episode model
     *
     * @return string
     */
    // public function getSqlEpisodeWhere();

    /**
     * The track id for the filter
     *
     * @return int
     */
    public function getTrackId()
    {
        if (isset($this->_data['gtap_id_track']) && $this->_data['gtap_id_track']) {
            return $this->_data['gtap_id_track'];
        }
    }

    /**
     * The number of days to wait between track creation
     *
     * @return int or null when no track creation or no wait days
     */
    public function getWaitDays()
    {
        if (isset($this->_data['gtap_create_wait_days'], $this->_data['gtap_create_track']) &&
                $this->_data['gtap_create_track']) {
            return intval($this->_data['gtap_create_wait_days']);
        }
    }

    /**
     * Should this track be created when it does not exist?
     *
     * @return boolean
     */
    public function isCreator()
    {
        return isset($this->_data['gtap_create_track']) && $this->_data['gtap_create_track'];
    }

    /**
     * Check a filter for a match
     *
     * @param \Gems\Agenda\Gems_Agenda_Appointment $appointment
     * @return boolean
     */
    // public function matchAppointment(\Gems_Agenda_Appointment $appointment);

    /**
     * Check a filter for a match
     *
     * @param \Gems\Agenda\EpisodeOfCare $episode
     * @return boolean
     */
    // public function matchEpisode(EpisodeOfCare $episode);

    /**
     * By default only object variables starting with '_' are serialized in order to
     * avoid serializing any resource types loaded by
     * \MUtil_Translate_TranslateableAbstract
     *
     * @return string
     */
    public function serialize() {
        $data = array();
        foreach (get_object_vars($this) as $name => $value) {
            if (! $this->filterRequestNames($name)) {
                $data[$name] = $value;
            }
        }
        return serialize($data);
    }

    /**
     * Restore parameter values
     *
     * @param string $data
     */
    public function unserialize($data) {

        foreach ((array) unserialize($data) as $name => $value) {
            $this->$name = $value;
        }
    }
}
