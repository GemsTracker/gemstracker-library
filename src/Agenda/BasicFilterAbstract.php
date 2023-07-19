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
abstract class BasicFilterAbstract
    implements AppointmentFilterInterface
{
    /**
     * Constant for filters that should always trigger
     */
    const MATCH_ALL_SQL = '1=1';

    /**
     * Constant for filters that should never trigger
     */
    const NO_MATCH_SQL = '1=0';

    public function __construct(
        protected readonly array $_data
    )
    {
        $this->afterLoad();
    }

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
    protected function afterLoad(): void
    { }

    /**
     * Load the object from a data array
     *
     * @param array $data
     */
    public function exchangeArray(array $data): void
    {
        $this->_data = $data;
        $this->afterLoad();
    }

    /**
     * Return the type of track creator this filter is
     *
     * @return int
     */
    public function getCreatorType(): int
    {
        return $this->_data['gtap_create_track'];
    }

    /**
     * The field id as it is recognized be the track engine
     *
     * @return string|null
     */
    public function getFieldId(): string|null
    {
        if (isset($this->_data['gtap_id_app_field']) && $this->_data['gtap_id_app_field']) {
            return FieldsDefinition::makeKey(
                    FieldMaintenanceModel::APPOINTMENTS_NAME,
                    $this->_data['gtap_id_app_field']
                    );
        }

        return null;
    }

    /**
     * The filter id
     *
     * @return int
     */
    public function getFilterId(): int
    {
        return $this->_data['gaf_id'];
    }

    /**
     * The name of the filter
     *
     * @return string
     */
    public function getName(): string
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
     * @return int|null
     */
    public function getTrackId(): int|null
    {
        if (isset($this->_data['gtap_id_track']) && $this->_data['gtap_id_track']) {
            return $this->_data['gtap_id_track'];
        }
        return null;
    }

    /**
     * The number of days to wait between track creation
     *
     * @return int|null null when no track creation or no wait days
     */
    public function getWaitDays(): int|null
    {
        if (isset($this->_data['gtap_create_wait_days'], $this->_data['gtap_create_track']) &&
                $this->_data['gtap_create_track']) {
            return intval($this->_data['gtap_create_wait_days']);
        }
        return null;
    }

    /**
     * Should this track be created when it does not exist?
     *
     * @return boolean
     */
    public function isCreator(): bool
    {
        return isset($this->_data['gtap_create_track']) && $this->_data['gtap_create_track'];
    }

    /**
     * Check a filter for a match
     *
     * @param \Gems\Agenda\Gems\Agenda\Appointment $appointment
     * @return boolean
     */
    // public function matchAppointment(\Gems\Agenda\Appointment $appointment);

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
     * \MUtil\Translate\TranslateableAbstract
     *
     * @return array
     */
    public function __serialize(): array
    {
        $data = [];
        foreach (get_object_vars($this) as $name => $value) {
            if (! $this->filterRequestNames($name)) {
                $data[$name] = $value;
            }
        }
        return $data;
    }

    /**
     * Restore parameter values
     *
     * @param array $data
     */
    public function __unserialize(array $data)
    {
        foreach ($data as $name => $value) {
            $this->$name = $value;
        }
    }
}
