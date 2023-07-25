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

use Gems\Agenda\Agenda;
use Gems\Agenda\Appointment;
use Gems\Util\Translated;
use MUtil\Translate\Translator;

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
    protected array|null $_dependsOn = ['gr2t_id_organization'];

    /**
     * Model settings for this field that may change depending on the dependsOn fields.
     *
     * @var array Null or an array of model settings that change for this field
     */
    protected array|null $_effecteds = ['multiOptions'];

    public function __construct(
        int $trackId,
        string $fieldKey,
        array $fieldDefinition,
        Translator $translator,
        Translated $translatedUtil,
        protected Agenda $agenda,
    ) {
        parent::__construct($trackId, $fieldKey, $fieldDefinition, $translator, $translatedUtil);
    }

    /**
     * Add the model settings like the elementClass for this field.
     *
     * elementClass is overwritten when this field is read only, unless you override it again in getDataModelSettings()
     *
     * @param array $settings The settings set so far
     */
    protected function addModelSettings(array &$settings): void
    {
        $empty = $this->translatedUtil->getEmptyDropdownArray();

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
    public function calculateFieldInfo(mixed $currentValue, array $fieldData): mixed
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
    public function calculateFieldValue(mixed $currentValue, array $fieldData, array $trackData): mixed
    {
        $calcUsing = $this->getCalculationFields($fieldData);

        if ($calcUsing) {

            // Get the used fields with values
            foreach (array_filter($calcUsing) as $value) {
                $appointment = $this->agenda->getAppointment($value);

                if ($appointment->exists) {
                    return $this->getId($appointment);
                }
            }
        }

        return $currentValue;
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
    public function getDataModelDependencyChanges(array $context, bool $new): array|null
    {
        if ($this->isReadOnly()) {
            return null;
        }
        $empty  = $this->translatedUtil->getEmptyDropdownArray();

        $output['multiOptions'] = $empty + $this->getLookup($context['gr2t_id_organization']);

        return $output;
    }
    
    /**
     * Return the appropriate Id for the given appointment
     * 
     * @param \Gems\Agenda\Appointment $appointment
     * @return int
     */
    abstract protected function getId(Appointment $appointment): int|null;
            
    /**
     * Return the lookup array for this field
     * 
     * @param int $organizationId Organization Id
     * @return array
     */
    abstract protected function getLookup(int|null $organizationId = null): array;
}