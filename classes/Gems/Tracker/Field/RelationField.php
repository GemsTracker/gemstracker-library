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
 * @version    $Id: RelationField.php $
 */

namespace Gems\Tracker\Field;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker_Field
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 18-mrt-2015 11:43:04
 */
class RelationField extends FieldAbstract
{
    /**
     * Respondent track fields that this field's settings are dependent on.
     *
     * @var array Null or an array of respondent track fields.
     */
    protected $_dependsOn = array('gr2t_id_user');

    /**
     * Model settings for this field that may change depending on the dependsOn fields.
     *
     * @var array Null or an array of model settings that change for this field
     */
    protected $_effecteds = array('multiOptions');

    /**
     * Query for respondent infomration.
     *
     * Can be changed by project specific classes
     *
     * @var string
     */
    protected $_sql = "SELECT grr_id,
                    CONCAT(
                        COALESCE(CONCAT(grr_type, ' '), ''),
                        CASE WHEN gsf_id_user IS NULL
                            THEN CONCAT(
                                COALESCE(CONCAT(grr_first_name, ' '), ''),
                                COALESCE(grr_last_name, '')
                                )
                            ELSE CONCAT(
                                COALESCE(CONCAT(gsf_first_name, ' '), ''),
                                COALESCE(CONCAT(gsf_surname_prefix, ' '), ''),
                                COALESCE(gsf_last_name, '')
                                )
                        END
                        ) AS name
                FROM gems__respondent_relations LEFT JOIN gems__staff ON gsf_id_user = grr_id_staff
                ";
    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

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
        $settings['elementClass']   = 'Select';
        $settings['formatFunction'] = array($this, 'showRelation');
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

        // Display nice
        $sql = $this->_sql . "WHERE grr_id = ?";
        $row = $this->db->fetchRow($sql, $currentValue);

        if ($row && isset($row['name'])) {
            return $row['name'];
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
    public function getDataModelDependyChanges(array $context, $new)
    {
        if ($this->isReadOnly()) {
            return null;
        }

        $sql    = $this->_sql ."WHERE grr_id_respondent = ? ORDER BY grr_type";
        $empty  = $this->util->getTranslated()->getEmptyDropdownArray();

        $output['multiOptions'] = $empty + $this->db->fetchPairs($sql, $context['gr2t_id_user']);

        return $output;
    }

    /**
     * Dispaly an appoitment as text
     *
     * @param value $value
     * @return string
     */
    public function showRelation($value)
    {
        // Display nicer

        return $value;
    }
}
