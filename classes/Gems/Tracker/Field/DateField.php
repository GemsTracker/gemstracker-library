<?php

/**
 * Copyright (c) 2015, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL MAGNAFACTA BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
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
            return $value;
        }

        if ($currentValue instanceof \Zend_Date) {
            $value = $currentValue->toString($this->zendDateTimeFormat);
        } elseif ($currentValue instanceof DateTime) {
            $value = date($this->phpDateTimeFormat, $currentValue->getTimestamp());
        } else {
            $value = $currentValue;
        }

        if (! $currentValue) {
            return null;
        } else {
            return array($this->getLabel(), ' ', $value);
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
            $displayFormat = \MUtil_Model_Bridge_FormBridge::getFixedOption('date', 'dateFormat');

            try {
                return \MUtil_Date::format($currentValue, $saveFormat, $displayFormat);
            } catch (\Zend_Exception $e) {
                if (\Zend_Date::isDate($currentValue, $saveFormat)) {
                    return $currentValue;
                }
                throw $e;
            }
        }

        return (string) $currentValue;
    }
}
