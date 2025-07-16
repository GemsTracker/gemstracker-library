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
use Gems\Agenda\Agenda;
use Gems\Util\Translated;
use Laminas\Db\Sql\Expression;
use MUtil\Model;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\Model\Type\AbstractDateType;

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
    
    public array $allowedDateFormats = [
        'Y-m-d H:i:s',
        'Y-m-d H:i',
        'Y-m-d',
        'c',
        'd-m-Y H:i',
        'd-m-Y H:i',
        'd-m-Y H:i:s'
    ];

    /**
     * The format string for outputting dates
     *
     * @var string
     */
    protected string $phpDateTimeFormat = 'j M Y';

    /**
     * The model type
     *
     * @var int
     */
    protected int $type = MetaModelInterface::TYPE_DATE;

    public function __construct(
        int $trackId,
        string $fieldKey,
        array $fieldDefinition,
        TranslatorInterface $translator,
        Translated $translatedUtil,
        protected readonly Agenda $agenda,
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
        $settings['elementClass']  = 'Date';
        $settings['dateFormat']    = $this->getDateFormat();
        $settings['storageFormat'] = $this->getStorageFormat();
        $settings['type']          = $this->type;
    }

    /**
     * Calculation the field info display for this type
     *
     * @param mixed $currentValue The current value
     * @param array $fieldData The other values loaded so far
     * @return mixed the new value
     */
    public function calculateFieldInfo(mixed $currentValue, array $fieldData): mixed
    {
        if ((null === $currentValue) ||
                ($currentValue instanceof \Zend_Db_Expr) ||
                ($currentValue instanceof Expression) ||
                (is_string($currentValue) && str_starts_with($currentValue, 'current_'))) {
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
     * @param mixed $currentValue The current value
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
    protected function getDateFormat(): string
    {
        return Model::getTypeDefault(Model::TYPE_DATE, 'dateFormat');
    }

    /**
     * Get the date display format (zend style)
     *
     * @return string
     */
    protected function getStorageFormat(): string
    {
        return Model::getTypeDefault(Model::TYPE_DATE, 'storageFormat');
    }

    /**
     * Calculate the field value using the current values
     *
     * @param mixed $currentValue The current value
     * @param array $fieldData The other values loaded so far
     * @return mixed the new value
     */
    public function onFieldDataLoad(mixed $currentValue, array $fieldData): mixed
    {
        if (empty($currentValue)) {
            return null;
        }

        return DateTimeImmutable::createFromFormat($this->getStorageFormat(), $currentValue);
    }

    /**
     * Converting the field value when saving to a respondent track
     *
     * @param mixed $currentValue The current value
     * @param array $fieldData The other values loaded so far
     * @return mixed the new value
     */
    public function onFieldDataSave(mixed $currentValue, array $fieldData): mixed
    {
        if ((null === $currentValue) ||
                ($currentValue instanceof \Zend_Db_Expr) ||
                ($currentValue instanceof Expression) ||
                (is_string($currentValue) && str_starts_with($currentValue, 'current_'))) {
            return $currentValue;
        }
        if ('' == $currentValue) {
            return null;
        }

        $saveFormat = $this->getStorageFormat();

        if ($currentValue instanceof \DateTimeInterface) {
            return $currentValue->format($saveFormat);

        } else {
            $displayFormat = $this->getDateFormat();

            $saveDate = AbstractDateType::toDate($currentValue, $saveFormat, $displayFormat, true);
            if ($saveDate instanceof \DateTimeInterface) {
                return $saveDate->format($saveFormat);
            }
        }

        return (string) $currentValue;
    }
}
