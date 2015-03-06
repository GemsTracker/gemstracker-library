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
 * @version    $Id: FieldAbstract.php $
 */

namespace Gems\Tracker\Field;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker_Field
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 4-mrt-2015 11:40:28
 */
abstract class FieldAbstract extends \MUtil_Translate_TranslateableAbstract implements FieldInterface
{
    /**
     *
     * @var array  Field definition array
     */
    protected $_fieldDefinition;

    /**
     *
     * @var string
     */
    protected $_fieldId;

    /**
     *
     * @var int gems__tracks id for this field
     */
    protected $_trackId;

    /**
     *
     * @param int $trackId gems__tracks id for this field
     * @param string $key The field key
     * @param array $fieldDefinition Field definition array
     */
    public function __construct($trackId, $key, array $fieldDefinition)
    {
        $this->_trackId         = $trackId;
        $this->_fieldId         = $key;
        $this->_fieldDefinition = $fieldDefinition;
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
        return $currentValue;
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
        return $this;
    }

    /**
     *
     * @return The field code
     */
    public function getCode()
    {
        return $this->_fieldDefinition['gtf_field_code'];
    }

    /**
     * Get the fields that should be used for calculation,
     * first field to use first.
     *
     * I.e. the last selected field in field maintenance
     * is the first field in the output array.
     *
     * @param array $fieldData The fields being saved
     * @return array [fieldKey => fieldValue]
     */
    public function getCalculationFields(array $fieldData)
    {
        $output = array();

        // Perform automatic calculation
        if (isset($this->_fieldDefinition['gtf_calculate_using'])) {
            $sources = explode(
                    \Gems_Tracker_Model_FieldMaintenanceModel::FIELD_SEP,
                    $this->_fieldDefinition['gtf_calculate_using']
                    );

            foreach ($sources as $source) {
                if (isset($fieldData[$source]) && $fieldData[$source]) {
                    $output[$source] = $fieldData[$source];
                } else {
                    $output[$source] = null;
                }
            }
        }
        return array_reverse($output, true);
    }

    /**
     *
     * @return The track field id
     */
    public function getFieldId()
    {
        return $this->_fieldDefinition['gtf_id_field'];
    }


    /**
     *
     * @return The track field sub (model) value
     */
    public function getFieldSub()
    {
        return $this->_fieldDefinition['sub'];
    }

    /**
     *
     * @return The field label
     */
    public function getLabel()
    {
        return $this->_fieldDefinition['gtf_field_name'];
    }

    /**
     * Calculation the field value when loading from a respondent track
     *
     * @param array $currentValue The current value
     * @param array $fieldData The other values loaded so far
     * @return mixed the new value
     */
    public function onFieldDataLoad($currentValue, array $fieldData)
    {
        return $currentValue;
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
        return $currentValue;
    }

    /**
     * Should this field be added to the track info
     *
     * @return boolean
     */
    public function toTrackInfo()
    {
        return $this->_fieldDefinition['gtf_to_track_info'];
    }
}
