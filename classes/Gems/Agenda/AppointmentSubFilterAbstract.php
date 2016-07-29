<?php

/**
 *
 * @package    Gems
 * @subpackage Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id: AppointmentSubFilterAbstract.php $
 */

namespace Gems\Agenda;

/**
 *
 *
 * @package    Gems
 * @subpackage Agenda
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 17-okt-2014 14:46:23
 */
abstract class AppointmentSubFilterAbstract extends AppointmentFilterAbstract
{
    /**
     *
     * @var array of AppointmentFilterInterface instances
     */
    protected $_subFilters = array();

    /**
     *
     * @var \Gems_Agenda
     */
    protected $agenda;

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
    {
        if ($this->_data &&
                $this->agenda instanceof \Gems_Agenda &&
                ! $this->_subFilters) {

            // Flexible determination of filters to load. Save for future expansion of numbe rof fields
            $i         = 1;
            $field     = 'gaf_filter_text' . $i;
            $filterIds = array();
            while (array_key_exists($field, $this->_data)) {
                if ($this->_data[$field]) {
                    $filterIds[] = intval($this->_data[$field]);
                }
                $i++;
                $field = 'gaf_filter_text' . $i;
            }

            if ($filterIds) {
                $filterObjects = $this->agenda->getFilters("SELECT *
                    FROM gems__appointment_filters 
                    WHERE gaf_id IN (" . implode(', ', $filterIds) . ")
                    ORDER BY gaf_id_order");

                // The order we get the filters may not be the same as the one in which they are specified
                foreach ($filterIds as $id) {
                    foreach ($filterObjects as $filterObject) {
                        if ($filterObject instanceof AppointmentFilterInterface) {
                            if ($filterObject->getFilterId() == $id) {
                                $this->_subFilters[$id] = $filterObject;
                                break;
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Check a filter for a match
     *
     * @param \Gems\Agenda\Gems_Agenda_Appointment $appointment
     * @return boolean
     */
    // public function matchAppointment(\Gems_Agenda_Appointment $appointment);
}
