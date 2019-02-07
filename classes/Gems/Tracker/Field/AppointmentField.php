<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker_Field
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Field;

use Gems\Date\Period;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker_Field
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 4-mrt-2015 11:43:04
 */
class AppointmentField extends FieldAbstract
{
    /**
     * Respondent track fields that this field's settings are dependent on.
     *
     * @var array Null or an array of respondent track fields.
     */
    protected $_dependsOn = array('gr2t_id_user', 'gr2t_id_organization');

    /**
     * Model settings for this field that may change depending on the dependsOn fields.
     *
     * @var array Null or an array of model settings that change for this field
     */
    protected $_effecteds = array('multiOptions');

    /**
     * The last active appointment in any field
     *
     * Shared among all field instances saving to the same respondent track id
     *
     * @var array of \Gems_Agenda_Appointment)
     */
    protected static $_lastActiveAppointment = array();

    /**
     * The last active appointment in any field
     *
     * Shared among all field instances saving to the same respondent track id
     *
     * @var array of $_lastActiveKey => array(appId => appId)
     */
    protected static $_lastActiveAppointmentIds = array();

    /**
     * The key for the current calculation to self::$_lastActiveAppointment  and
     * self::$_lastActiveAppointmentIds
     *
     * @var mixed
     */
    protected $_lastActiveKey;

    /**
     * The format string for outputting appointments
     *
     * @var string
     */
    protected $appointmentTimeFormat = 'dd MMM yyyy HH:mm';

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var \Gems_Menu
     */
    protected $menu;

    /**
     * Required
     *
     * @var \Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     *
     * @var \Gems_Util
     */
    protected $util;

    /**
     * Add the model settings like the elementClass for this field.
     *
     * elementClass is overwritten when this field is read only, unless you override it again in getDataModelSettings()
     *
     * @param array $settings The settings set so far
     */
    protected function addModelSettings(array &$settings)
    {
        $settings['elementClass']   = 'Select';
        $settings['formatFunction'] = array($this, 'showAppointment');
    }

    /**
     * Calculation the field info display for this type
     *
     * @param array $currentValue The current value
     * @param array $fieldData The other values loaded so far
     * @return mixed the new value
     */
    public function calculateFieldInfo($currentValue, array $fieldData)
    {
        if (! $currentValue) {
            return $currentValue;
        }

        $agenda      = $this->loader->getAgenda();
        $appointment = $agenda->getAppointment($currentValue);

        if ($appointment && $appointment->isActive()) {
            $time = $appointment->getAdmissionTime();

            if ($time) {
                return $time->toString($this->appointmentTimeFormat);
            }
        }

        return null;
    }

    /**
     * Calculate the field value using the current values
     *
     * @param array $currentValue The current value
     * @param array $fieldData The other known field values
     * @param array $trackData The currently available track data (track id may be empty)
     * @return mixed the new value
     */
    public function calculateFieldValue($currentValue, array $fieldData, array $trackData)
    {
        if ($currentValue || isset($this->_fieldDefinition['gtf_filter_id'])) {
            $agenda = $this->loader->getAgenda();

            if ($this->_lastActiveKey && isset($this->_fieldDefinition['gtf_filter_id'])) {
                $fromDate   = false;
                $lastActive = self::$_lastActiveAppointment[$this->_lastActiveKey];

                if (($lastActive instanceof \Gems_Agenda_Appointment) && $lastActive->isActive()) {
                    $fromDate = $lastActive->getAdmissionTime();
                }

                if ((! $fromDate) && isset($trackData['gr2t_start_date']) && $trackData['gr2t_start_date']) {

                    if ($trackData['gr2t_start_date'] instanceof \Zend_Date) {
                        $fromDate = $trackData['gr2t_start_date'];
                    } else {
                        $fromDate = new \MUtil_Date($trackData['gr2t_start_date'], \Gems_Tracker::DB_DATETIME_FORMAT);
                    }
                    // Always use start of the day for start date comparisons
                    $fromDate->setTime('00:00:00');
                }

                if ($fromDate instanceof \MUtil_Date) {
                    $select = $agenda->createAppointmentSelect(array('gap_id_appointment'));
                    $select->onlyActive()
                            ->forFilterId($this->_fieldDefinition['gtf_filter_id'])
                            ->forRespondent($trackData['gr2t_id_user'], $trackData['gr2t_id_organization']);

                    $minDate = Period::applyPeriod(
                            $fromDate,
                            $this->_fieldDefinition['gtf_min_diff_unit'],
                            $this->_fieldDefinition['gtf_min_diff_length']
                            );
                    if ($this->_fieldDefinition['gtf_max_diff_exists']) {
                        $maxDate = Period::applyPeriod(
                                $fromDate,
                                $this->_fieldDefinition['gtf_max_diff_unit'],
                                $this->_fieldDefinition['gtf_max_diff_length']
                                );
                    } else {
                        $maxDate = null;
                    }
                    if ($this->_fieldDefinition['gtf_min_diff_length'] > 0) {
                        $select->forPeriod($minDate, $maxDate, true);
                    } else {
                        $select->forPeriod($maxDate, $minDate, false);
                    }

                    if ($this->_fieldDefinition['gtf_uniqueness']) {
                        switch ($this->_fieldDefinition['gtf_uniqueness']) {
                            case 1: // Track instances may link only once to an appointment
                                $select->uniqueInTrackInstance(
                                        self::$_lastActiveAppointmentIds[$this->_lastActiveKey]
                                        );
                                break;

                            case 2: // Tracks of this type may link only once to an appointment
                                if (isset($trackData['gr2t_id_respondent_track'])) {
                                    $respTrackId = $trackData['gr2t_id_respondent_track'];
                                } else {
                                    $respTrackId = null;
                                }
                                $select->uniqueForTrackId(
                                        $this->_trackId,
                                        $respTrackId,
                                        self::$_lastActiveAppointmentIds[$this->_lastActiveKey]
                                        );
                                break;

                            // default:
                        }
                    }

                    // Query ready
                    // echo "\n" . $select->getSelect()->__toString() . "\n";
                    $newValue = $select->fetchOne();

                    if ($newValue) {
                        $currentValue = $newValue;
                    }
                }
            }

            if ($this->_lastActiveKey && $currentValue) {
                $appointment = $agenda->getAppointment($currentValue);

                if ($appointment->isActive()) {
                    self::$_lastActiveAppointment[$this->_lastActiveKey] = $appointment;
                    self::$_lastActiveAppointmentIds[$this->_lastActiveKey][$currentValue] = $currentValue;
                }
            }
        }

        return $currentValue;

    }

    /**
     * Signal the start of a new calculation round (for all fields)
     *
     * @param array $trackData The currently available track data (track id may be empty)
     * @return \Gems\Tracker\Field\FieldAbstract
     */
    public function calculationStart(array $trackData)
    {
        if (isset($trackData['gr2t_id_respondent_track'])) {
            $this->_lastActiveKey = $trackData['gr2t_id_respondent_track'];
        } elseif (isset($trackData['gr2t_id_user'], $trackData['gr2t_id_organization'])) {
            $this->_lastActiveKey = $trackData['gr2t_id_user'] . '__' . $trackData['gr2t_id_organization'];
        } else {
            $this->_lastActiveKey = false;
        }
        if ($this->_lastActiveKey) {
            self::$_lastActiveAppointment[$this->_lastActiveKey]    = null;
            self::$_lastActiveAppointmentIds[$this->_lastActiveKey] = array();
        }

        return $this;
    }

    /**
     * Returns the changes to the model for this field that must be made in an array consisting of
     *
     * <code>
     *  array(setting1 => $value1, setting2 => $value2, ...),
     * </code>
     *
     * By using [] array notation in the setting array key you can append to existing
     * values.
     *
     * Use the setting 'value' to change a value in the original data.
     *
     * When a 'model' setting is set, the workings cascade.
     *
     * @param array $context The current data this object is dependent on
     * @param boolean $new True when the item is a new record not yet saved
     * @return array (setting => value)
     */
    public function getDataModelDependyChanges(array $context, $new)
    {
        if ($this->isReadOnly()) {
            return null;
        }

        $agenda = $this->loader->getAgenda();
        $empty  = $this->util->getTranslated()->getEmptyDropdownArray();

        $output['multiOptions'] = $empty + $agenda->getActiveAppointments(
                $context['gr2t_id_user'],
                $context['gr2t_id_organization']
                );

        return $output;
    }

    /**
     * Dispaly an appoitment as text
     *
     * @param value $value
     * @return string
     */
    public function showAppointment($value)
    {
        if (! $value) {
            return $this->_('Unknown');
        }
        if ($value instanceof \Gems_Agenda_Appointment) {
            $appointment = $value;
        } else {
            $appointment = $this->loader->getAgenda()->getAppointment($value);
        }
        if ($appointment instanceof \Gems_Agenda_Appointment) {
            if (! $this->menu instanceof \Gems_Menu) {
                $this->menu = $this->loader->getMenu();
            }
            $menuItem = $this->menu->findAllowedController('appointment', 'show');
            if ($menuItem) {
                if (! $this->request) {
                    $this->request = \Zend_Controller_Front::getInstance()->getRequest();
                }
                $href = $menuItem->toHRefAttribute(
                        array('gap_id_appointment' => $appointment->getId()),
                        $this->request
                        );
                if ($href) {
                    return \MUtil_Html::create('a', $href, $appointment->getDisplayString());
                }
            }
            return $appointment->getDisplayString();
        }

        return $value;
    }
}
