<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker_Field
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Field;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker_Field
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.5
 */
abstract class AppointmentDerivedFieldAbstract extends FieldAbstract
{
    /**
     * Respondent track fields that this field's settings are dependent on.
     *
     * @var array Null or an array of respondent track fields.
     */
    protected $_dependsOn = array('gr2t_id_organization');

    /**
     * Model settings for this field that may change depending on the dependsOn fields.
     *
     * @var array Null or an array of model settings that change for this field
     */
    protected $_effecteds = array('multiOptions');
    
    /**
     *
     * @var \Gems_Agenda 
     */
    protected $agenda;

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

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
        $empty = $this->util->getTranslated()->getEmptyDropdownArray();

        $settings['elementClass'] = 'Select';
        $settings['multiOptions'] = $empty + $this->getLookup();
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

        $lookup = $this->getLookup();

        if (isset($lookup[$currentValue])) {
            return $lookup[$currentValue];
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
        $calcUsing = $this->getCalculationFields($fieldData);

        if ($calcUsing) {
            $agenda = $this->getAgenda();

            // Get the used fields with values
            foreach (array_filter($calcUsing) as $value) {
                $appointment = $agenda->getAppointment($value);

                if ($appointment->exists) {
                    return $this->getId($appointment);
                }
            }
        }

        return $currentValue;
    }
    
    /**
     * Retreive the agenda if not injected
     * 
     * @return \Gems_Agenda
     */
    protected function getAgenda()
    {
        if (!$this->agenda) {
            $this->agenda = $this->loader->getAgenda();
        }
        
        return $this->agenda;
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
        $empty  = $this->util->getTranslated()->getEmptyDropdownArray();

        $output['multiOptions'] = $empty + $this->getLookup($context['gr2t_id_organization']);

        return $output;
    }
    
    /**
     * Return the appropriate Id for the given appointment
     * 
     * @param \Gems_Agenda_Appointment $appointment
     * @return int
     */
    abstract protected function getId(\Gems_Agenda_Appointment $appointment);
            
    /**
     * Return the lookup array for this field
     * 
     * @param int $organizationId Organization Id
     * @return array
     */
    abstract protected function getLookup($organizationId = null);
}