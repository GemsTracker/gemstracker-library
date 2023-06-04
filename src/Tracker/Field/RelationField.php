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

use Gems\Util\Translated;

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
                        CASE WHEN gsf_id_user IS NULL
                            THEN CONCAT_WS(' ',
                                grr_type,
                                grr_first_name,
                                grr_last_name
                                )
                            ELSE CONCAT_WS(' ',
                                grr_type,
                                gsf_first_name,
                                gsf_surname_prefix,
                                gsf_last_name
                                )
                        END
                        AS name
                FROM gems__respondent_relations LEFT JOIN gems__staff ON gsf_id_user = grr_id_staff
                ";
    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     * @var Translated
     */
    protected $translatedUtil;

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
        $empty  = $this->translatedUtil->getEmptyDropdownArray();

        $output['multiOptions'] = $empty + $this->db->fetchPairs($sql, $context['gr2t_id_user']);

        return $output;
    }

    /**
     * Display a relation as text
     *
     * @param mixed $value
     * @return string
     */
    public function showRelation($value)
    {
        // Display nicer
        $display = $this->calculateFieldInfo($value, array());
        if ($value == $display) {
            $display = $this->_('-');
        }

        return $display;
    }
}
