<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker_Field
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: DateField.php $
 */

namespace Gems\Tracker\Field;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker_Field
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 4-mrt-2015 11:43:37
 */
class DateField extends FieldAbstract
{
    
    public $allowedDateFormats = [
        'yyyy-MM-dd HH:mm:ss',
        'yyyy-MM-dd HH:mm',
        'yyyy-MM-dd',
        'c',
        'dd-MM-yyyy',
        'dd-MM-yyyy HH:mm',
        'dd-MM-yyyy HH:mm:ss'
    ];

    
    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     * The format string for outputting dates
     *
     * @var string
     */
    protected $phpDateTimeFormat = 'j M Y';

    /**
     * The model type
     *
     * @var int
     */
    protected $type = \MUtil_Model::TYPE_DATE;

    /**
     * The format string for outputting dates
     *
     * @var string
     */
    protected $zendDateTimeFormat = 'dd MMM yyyy';

    /**
     * Add the model settings like the elementClass for this field.
     *
     * elementClass is overwritten when this field is read only, unless you override it again in getDataModelSettings()
     *
     * @param array $settings The settings set so far
     */
    protected function addModelSettings(array &$settings)
    {
        $settings['elementClass']  = 'Date';
        $settings['dateFormat']    = $this->getDateFormat();
        $settings['storageFormat'] = $this->getStorageFormat();
        $settings['type']          = $this->type;
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
        if ((null === $currentValue) ||
                ($currentValue instanceof \Zend_Db_Expr) ||
                \MUtil_String::startsWith($currentValue, 'current_', true)) {
            return null;
        }

        if ($currentValue instanceof \Zend_Date) {
            $value = $currentValue->toString($this->zendDateTimeFormat);
        } elseif ($currentValue instanceof DateTime) {
            $value = date($this->phpDateTimeFormat, $currentValue->getTimestamp());
        } else {
            $value = $currentValue;
        }

        if ($currentValue) {
            return $value;
        } else {
            return null;
        }
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
            $agenda = $this->loader->getAgenda();

            // Get the used fields with values
            foreach (array_filter($calcUsing) as $value) {
                $appointment = $agenda->getAppointment($value);

                if ($appointment->exists) {
                    if ($appointment->isActive()) {
                        return $appointment->getAdmissionTime();
                    } else {
                        // Empty the Date field if there are appointments, but these
                        // are not active 
                        $currentValue = null;
                    }
                }
            }
        }

        if ($currentValue instanceof \MUtil_Date) {
            return $currentValue;
        }
        if ($currentValue) {
            return \MUtil_Date::ifDate($currentValue, $this->allowedDateFormats);
        }

        return $currentValue;
    }

    /**
     * Get the date display format (zend style)
     *
     * @return string
     */
    protected function getDateFormat()
    {
        return \MUtil_Model_Bridge_FormBridge::getFixedOption('date', 'dateFormat');
    }

    /**
     * Get the date display format (zend style)
     *
     * @return string
     */
    protected function getStorageFormat()
    {
        return \Gems_Tracker::DB_DATE_FORMAT;
    }

    /**
     * Calculate the field value using the current values
     *
     * @param array $currentValue The current value
     * @param array $fieldData The other values loaded so far
     * @return mixed the new value
     */
    public function onFieldDataLoad($currentValue, array $fieldData)
    {
        if (empty($currentValue)) {
            return null;
        }

        return new \MUtil_Date($currentValue, $this->getStorageFormat());
    }

    /**
     * Converting the field value when saving to a respondent track
     *
     * @param array $currentValue The current value
     * @param array $fieldData The other values loaded so far
     * @return mixed the new value
     */
    public function onFieldDataSave($currentValue, array $fieldData)
    {
        if ((null === $currentValue) ||
                ($currentValue instanceof \Zend_Db_Expr) ||
                \MUtil_String::startsWith($currentValue, 'current_', true)) {
            return $currentValue;
        }

        $saveFormat = $this->getStorageFormat();

        if ($currentValue instanceof \Zend_Date) {
            return $currentValue->toString($saveFormat);

        } else {
            $displayFormat = $this->getDateFormat();

            $saveDate = \MUtil_Date::ifDate($currentValue, array($displayFormat, $saveFormat, \Gems_Tracker::DB_DATETIME_FORMAT));
            if ($saveDate instanceof \Zend_Date) {
                return $saveDate->toString($saveFormat);
            }
        }

        return (string) $currentValue;
    }
}
