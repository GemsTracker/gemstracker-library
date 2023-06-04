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

use DateTimeInterface;
use DateTimeImmutable;
use MUtil\Model;

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
        'Y-m-d H:i:s',
        'Y-m-d H:i',
        'Y-m-d',
        'c',
        'd-m-Y H:i',
        'd-m-Y H:i',
        'd-m-Y H:i:s'
    ];

    /**
     *
     * @var \Gems\Loader
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
    protected $type = \MUtil\Model::TYPE_DATE;

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
                \MUtil\StringUtil\StringUtil::startsWith($currentValue, 'current_', true)) {
            return null;
        }

        if ($currentValue instanceof DateTimeInterface) {
            $value = $currentValue->format($this->phpDateTimeFormat);
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
                    return $appointment->getAdmissionTime();
                }
            }
        }

        if ($currentValue instanceof DateTimeInterface) {
            return $currentValue;
        }
        if ($currentValue) {
            return Model::getDateTimeInterface($currentValue, $this->allowedDateFormats);
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
        return Model::getTypeDefault(Model::TYPE_DATE, 'dateFormat');
    }

    /**
     * Get the date display format (zend style)
     *
     * @return string
     */
    protected function getStorageFormat()
    {
        return Model::getTypeDefault(Model::TYPE_DATE, 'storageFormat');;
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

        return DateTimeImmutable::createFromFormat($this->getStorageFormat(), $currentValue);
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
                \MUtil\StringUtil\StringUtil::startsWith($currentValue, 'current_', true)) {
            return $currentValue;
        }

        $saveFormat = $this->getStorageFormat();

        if ($currentValue instanceof DateTimeInterface) {
            return $currentValue->format($saveFormat);

        } else {
            $displayFormat = $this->getDateFormat();

            $saveDate = Model::getDateTimeInterface($currentValue, [$displayFormat, $saveFormat, \Gems\Tracker::DB_DATETIME_FORMAT]);
            if ($saveDate instanceof \DateTimeInterface) {
                return $saveDate->toString($saveFormat);
            }
        }

        return (string) $currentValue;
    }
}
