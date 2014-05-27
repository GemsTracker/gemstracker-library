<?php

/**
 * Copyright (c) 2012, Erasmus MC
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
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $id: FieldMaintenanceModel.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class Gems_Tracker_Model_FieldMaintenanceModel extends MUtil_Model_UnionModel
{
    /**
     * Constant name to id appointment items
     */
    const APPOINTMENTS_NAME = 'a';

    /**
     * Constant name to id field items
     */
    const FIELDS_NAME = 'f';

    /**
     * Option seperator for fields
     */
    const FIELD_SEP = '|';

    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * The fields that can be calculated using an appointment as input
     *
     * @var array
     */
    protected $fromAppointments = array('caretaker', 'location');

    /**
     *
     * @var Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var Zend_Translate
     */
    protected $translate;

    /**
     *
     * @var Zend_Translate_Adapter
     */
    protected $translateAdapter;

    /**
     *
     * @var Gems_Util
     */
    protected $util;

    /**
     * The fields that use the values TextArea
     *
     * @var array
     */
    protected $valuesFields = array('multiselect', 'select');

    /**
     *
     * @param string $modelName Hopefully unique model name
     * @param string $modelField The name of the field used to store the sub model
     */
    public function __construct($modelName = 'fields_maintenance', $modelField = 'sub')
    {
        parent::__construct($modelName, $modelField);

        $model = new MUtil_Model_TableModel('gems__track_fields');
        Gems_Model::setChangeFieldsByPrefix($model, 'gtf');
        $this->addUnionModel($model, null, self::FIELDS_NAME);

        $model = new MUtil_Model_TableModel('gems__track_appointments');
        Gems_Model::setChangeFieldsByPrefix($model, 'gtap');

        $map = $model->getItemsOrdered();
        $map = array_combine($map, str_replace('gtap_', 'gtf_', $map));
        $map['gtap_id_app_field'] = 'gtf_id_field';

        $this->addUnionModel($model, $map, self::APPOINTMENTS_NAME);

        $model->addColumn(new Zend_Db_Expr("'appointment'"), 'gtf_field_type');
        $model->addColumn(new Zend_Db_Expr("NULL"), 'gtf_field_values');
        $model->addColumn(new Zend_Db_Expr("NULL"), 'gtf_calculate_using');

        $this->setKeys(array(
            Gems_Model::FIELD_ID => 'gtf_id_field',
            MUtil_Model::REQUEST_ID => 'gtf_id_track',
            ));
        $this->setClearableKeys(array(Gems_Model::FIELD_ID => 'gtf_id_field'));
        $this->setSort(array('gtf_id_order' => SORT_ASC));
    }

    /**
     * Copy from Zend_Translate_Adapter
     *
     * Translates the given string
     * returns the translation
     *
     * @param  string             $text   Translation string
     * @param  string|Zend_Locale $locale (optional) Locale/Language to use, identical with locale
     *                                    identifier, @see Zend_Locale for more information
     * @return string
     */
    public function _($text, $locale = null)
    {
        return $this->translateAdapter->_($text, $locale);
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * This function is no needed if the classes are setup correctly
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        $this->initTranslateable();
    }

    /**
     * Set those settings needed for the browse display
     *
     * @return Gems_Tracker_Model_FieldMaintenanceModel (continuation pattern)
     */
    public function applyBrowseSettings()
    {
        $yesNo = $this->util->getTranslated()->getYesNo();

        $this->set('gtf_id_order',     'label', $this->_('Order'));
        $this->set('gtf_field_name',   'label', $this->_('Name'));
        $this->set('gtf_field_code',   'label', $this->_('Code Name'),
                'description', $this->_('Optional extra name to link the field to program code.'));
        $this->set('gtf_field_type',   'label', $this->_('Type'),
                'multiOptions', $this->getFieldTypes(),
                'default', 'text',
                'order', $this->getOrder('gtf_id_track') + 5
                );
        $this->set('gtf_required',     'label', $this->_('Required'), 'multiOptions', $yesNo);
        $this->set('gtf_readonly',     'label', $this->_('Readonly'),
                'multiOptions', $yesNo,
                'description', $this->_('Check this box if this field is always set by code instead of the user.')
                );

        return $this;
    }

    /**
     * Set those settings needed for the detailed display
     *
     * @param int $trackId The current track id
     * @param int $data The currently known data
     * @return Gems_Tracker_Model_FieldMaintenanceModel (continuation pattern)
     */
    public function applyDetailSettings($trackId, array &$data)
    {
        $this->applyBrowseSettings();

        $this->set('gtf_id_track',          'label', $this->_('Track'),
                'multiOptions', $this->util->getTrackData()->getAllTracks()
                );
        $this->set('gtf_field_description', 'label', $this->_('Description'),
                'description', $this->_('Optional extra description to show the user.'));

        // Display of data field
        if (! (isset($data['gtf_field_type']) && $data['gtf_field_type'])) {
            if (isset($data[$this->_modelField]) && ($data[$this->_modelField] === self::APPOINTMENTS_NAME)) {
                $data['gtf_field_type'] = 'appointment';
            } else {
                if (isset($data['gtf_id_field']) && $data['gtf_id_field']) {
                    $data[Gems_Model::FIELD_ID] = $data['gtf_id_field'];
                }
                if (isset($data[Gems_Model::FIELD_ID])) {
                    $data['gtf_field_type'] = $this->db->fetchOne(
                            "SELECT gtf_field_type FROM gems__track_fields WHERE gtf_id_field = ?",
                            $data[Gems_Model::FIELD_ID]
                            );
                } else  {
                    if (! $this->has('gtf_field_type', 'default')) {
                        $this->set('gtf_field_type', 'default', 'text');
                    }
                    $data['gtf_field_type'] = $this->get('gtf_field_type', 'default');
                }
            }
        }
        if (! isset($data[$this->_modelField])) {
            $data[$this->_modelField] = $this->getModelNameForRow($data);
        }

        if (in_array($data['gtf_field_type'], $this->valuesFields)) {
            $this->set('gtf_field_values', 'label', $this->_('Values'),
                    'description', $this->_('Separate multiple values with a vertical bar (|)'),
                    'formatFunction', array($this, 'formatValues'));
        }
        if ($trackId && in_array($data['gtf_field_type'], $this->fromAppointments)) {
            $appFields = $this->db->fetchPairs("
                SELECT gtap_id_app_field, gtap_field_name
                    FROM gems__track_appointments
                    WHERE gtap_id_track = ?
                    ORDER BY gtap_id_order", $trackId);

            if ($appFields) {
                $options = $this->util->getTranslated()->getEmptyDropdownArray();
                foreach ($appFields as $id => $label) {
                    $key = Gems_Tracker_Engine_FieldsDefinition::makeKey(self::APPOINTMENTS_NAME, $id);
                    $options[$key] = $label;
                }

                $this->set('gtf_calculate_using', 'label', $this->_('Fill'),
                        'description', $this->_('Automatically fill this field using another field'),
                        'multiOptions', $options
                        );
            }
        }
    }

    /**
     * Set those values needed for editing
     *
     * @param int $trackId The current track id
     * @param int $data The currently known data
     * @return Gems_Tracker_Model_FieldMaintenanceModel (continuation pattern)
     */
    public function applyEditSettings($trackId, array $data)
    {
        $this->applyDetailSettings($trackId, $data);

        $noSubChange = false;
        $subId = $data[$this->_modelField];

        if ($subId == self::FIELDS_NAME) {
            $sql = 'SELECT gr2t2f_id_field
                FROM gems__respondent2track2field
                WHERE gr2t2f_id_field = ?';
        } elseif ($subId == self::APPOINTMENTS_NAME) {
            $sql = 'SELECT gr2t2a_id_app_field
                FROM gems__respondent2track2appointment
                WHERE gr2t2a_id_app_field = ?';
        } else {
            $sql = false;
        }
        if ($sql && isset($data[Gems_Model::FIELD_ID])) {
            $noSubChange = $this->db->fetchOne($sql, $data[Gems_Model::FIELD_ID]);
        }

        $this->set('gtf_id_field',          'elementClass', 'Hidden');
        $this->set('gtf_id_track',          'elementClass', 'Exhibitor');

        if ($noSubChange) {
            $this->set('gtf_field_type',    'elementClass', 'Exhibitor');
        } else {
            $this->set('gtf_field_type',    'elementClass', 'Select',
                    'onchange', 'this.form.submit();');
        }

        $this->set('gtf_field_name',        'elementClass', 'Text',
                'size', '30',
                'minlength', 4,
                'required', true,
                'validator', $this->createUniqueValidator(array('gtf_field_name','gtf_id_track'))
                );

        $this->set('gtf_id_order',          'elementClass', 'Text',
                'validators[int]', 'Int',
                'validators[gt]', new Zend_Validate_GreaterThan(0)
                );

        $this->set('gtf_field_code',        'elementClass', 'Text', 'minlength', 4);
        $this->set('gtf_field_description', 'elementClass', 'Text', 'size', 30);

        if ($this->has('gtf_field_values', 'label')) {
            $this->set('gtf_field_values',  'elementClass', 'Textarea',
                    'minlength', 4,
                    'rows', 4,
                    'required', true
                    );
        } else {
            $this->set('gtf_field_values',  'elementClass', 'Hidden');
        }
        $this->set('gtf_required',          'elementClass', 'CheckBox');
        $this->set('gtf_readonly',          'elementClass', 'CheckBox');
    }

    /**
     * Delete items from the model
     *
     * @param mixed $filter True to use the stored filter, array to specify a different filter
     * @return int The number of items deleted
     */
    public function delete($filter = true)
    {
        $rows = $this->load($filter);

        foreach ($rows as $row) {
            $name = $this->getModelNameForRow($row);

            if (self::FIELDS_NAME === $name) {
                $this->db->delete(
                        'gems__respondent2track2field',
                        $this->db->quoteInto('gr2t2f_id_field = ?', $field)
                        );

            } elseif (self::APPOINTMENTS_NAME === $name) {
                $this->db->delete(
                        'gems__respondent2track2appointment',
                        $this->db->quoteInto('gr2t2a_id_app_field= ?', $field)
                        );
            }
        }

        return parent::delete($filter);
    }

    /**
     * A ModelAbstract->setOnLoad() function that takes care of transforming a
     * dateformat read from the database to a Zend_Date format
     *
     * If empty or Zend_Db_Expression (after save) it will return just the value
     * currently there are no checks for a valid date format.
     *
     * @see MUtil_Model_ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @param boolean $isPost True when passing on post data
     * @return MUtil_Date|Zend_Db_Expr|string
     */
    public function formatLoadDate($value, $isNew = false, $name = null, array $context = array(), $isPost = false)
    {
        // If not empty or zend_db_expression and not already a zend date, we
        // transform to a Zend_Date using the ISO_8601 format
        if (empty($value) || $value instanceof Zend_Date || $value instanceof Zend_Db_Expr) {
            return $value;
        }

        $formats = array(
            Gems_Tracker::DB_DATE_FORMAT,
            MUtil_Model_Bridge_FormBridge::getFixedOption('date', 'dateFormat'),
            );

        if ($isPost) {
            // When posting try date format first
            $formats = array_reverse($formats);
        }

        foreach ($formats as $format) {
            if (Zend_Date::isDate($value, $format)) {
                return new MUtil_Date($value, $format);
            }
        }

        try {
            // Last try
            $tmpDate = new MUtil_Date($value, Zend_Date::ISO_8601);

        } catch (Exception $exc) {
            // On failure, we use the input value
            $tmpDate = $value;
        }

        return $tmpDate;
    }

    /**
     * A ModelAbstract->setOnSave() function that returns the input
     * date as a valid date.
     *
     * @see MUtil_Model_ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return Zend_Date
     */
    public function formatSaveDate($value, $isNew = false, $name = null, array $context = array())
    {
        if ((null === $value) ||
                ($value instanceof Zend_Db_Expr) ||
                MUtil_String::startsWith($value, 'current_', true)) {
            return $value;
        }

        $saveFormat = Gems_Tracker::DB_DATE_FORMAT;

        if ($value instanceof Zend_Date) {
            return $value->toString($saveFormat);

        } else {
            $displayFormat = MUtil_Model_Bridge_FormBridge::getFixedOption('date', 'dateFormat');

            try {
                return MUtil_Date::format($value, $saveFormat, $displayFormat);
            } catch (Zend_Exception $e) {
                if (Zend_Date::isDate($value, $saveFormat)) {
                    return $value;
                }
                throw $e;
            }
        }

        return $value;
    }

    /**
     * Put each value on a separate line
     *
     * @param string $values
     * @return \MUtil_Html_Sequence
     */
    public function formatValues($values)
    {
        return new MUtil_Html_Sequence(array('glue' => '<br/>'), explode('|', $values));
    }

    /**
     * The list of field types
     *
     * @return array of storage name => label
     */
    public function getFieldTypes()
    {
        return array(
            'select'      => $this->_('Select one'),
            'multiselect' => $this->_('Select multiple'),
            'appointment' => $this->_('Appointment'),
            'date'        => $this->_('Date'),
            'text'        => $this->_('Free text'),
            'location'    => $this->_('Location'),
            'caretaker'   => $this->_('Caretaker'),
            );
    }

    /**
     * Get the name of the union model that should be used for this row.
     *
     * @param array $row
     * @return string
     */
    protected function getModelNameForRow(array $row)
    {
        if (isset($row['gtf_field_type']) && ('appointment' === $row['gtf_field_type'])) {
            return self::APPOINTMENTS_NAME;
        }
        return self::FIELDS_NAME;
    }

    /**
     * Setting function for appointment select
     *
     * @param string $values The content of the gtf_field_values field
     * @param int $respondentId When null $patientNr is required
     * @param int $organizationId
     * @param string $patientNr Optional for when $respondentId is null
     * @param boolean $edit True when editing, false for display (detailed is assumed to be true)
     * @return array containing model settings
     */
    public function getSettingsForAppointment($values, $respondentId, $organizationId, $patientNr = null, $edit = true)
    {
        $agenda       = $this->loader->getAgenda();
        $appointments = $agenda->getActiveAppointments($respondentId, $organizationId, $patientNr);

        if ($edit) {
            $output['elementClass']  = 'Select';
        }
        $output['multiOptions'] = $this->util->getTranslated()->getEmptyDropdownArray() + $appointments;

        return $output;
    }

    /**
     * Setting function for caretaker select
     *
     * @param string $values The content of the gtf_field_values field
     * @param int $respondentId When null $patientNr is required
     * @param int $organizationId
     * @param string $patientNr Optional for when $respondentId is null
     * @param boolean $edit True when editing, false for display (detailed is assumed to be true)
     * @return array containing model settings
     */
    public function getSettingsForCaretaker($values, $respondentId, $organizationId, $patientNr = null, $edit = true)
    {
        $agenda     = $this->loader->getAgenda();
        $caretakers = $agenda->getHealthcareStaff($organizationId);

        if ($edit) {
            $output['elementClass']  = 'Select';
        }
        $output['multiOptions'] = $this->util->getTranslated()->getEmptyDropdownArray() + $caretakers;

        return $output;
    }

    /**
     * Setting function for date select
     *
     * @param string $values The content of the gtf_field_values field
     * @param int $respondentId When null $patientNr is required
     * @param int $organizationId
     * @param string $patientNr Optional for when $respondentId is null
     * @param boolean $edit True when editing, false for display (detailed is assumed to be true)
     * @return array containing model settings
     */
    public function getSettingsForDate($values, $respondentId, $organizationId, $patientNr = null, $edit = true)
    {
        if ($edit) {
            $output['elementClass']  = 'Date';
        }
        $output['dateFormat']    = MUtil_Model_Bridge_FormBridge::getFixedOption('date', 'dateFormat');
        $output['storageFormat'] = Gems_Tracker::DB_DATE_FORMAT;

        $output[MUtil_Model_ModelAbstract::LOAD_TRANSFORMER] = array($this, 'formatLoadDate');
        $output[MUtil_Model_ModelAbstract::SAVE_TRANSFORMER] = array($this, 'formatSaveDate');

        return $output;
    }

    /**
     * Setting function for location select
     *
     * @param string $values The content of the gtf_field_values field
     * @param int $respondentId When null $patientNr is required
     * @param int $organizationId
     * @param string $patientNr Optional for when $respondentId is null
     * @param boolean $edit True when editing, false for display (detailed is assumed to be true)
     * @return array containing model settings
     */
    public function getSettingsForLocation($values, $respondentId, $organizationId, $patientNr = null, $edit = true)
    {
        $agenda    = $this->loader->getAgenda();
        $locations = $agenda->getLocations($organizationId);

        if ($edit) {
            $output['elementClass']  = 'Select';
        }
        $output['multiOptions'] = $this->util->getTranslated()->getEmptyDropdownArray() + $locations;

        return $output;
    }

    /**
     * Setting function for multi select
     *
     * @param string $values The content of the gtf_field_values field
     * @param int $respondentId When null $patientNr is required
     * @param int $organizationId
     * @param string $patientNr Optional for when $respondentId is null
     * @param boolean $edit True when editing, false for display (detailed is assumed to be true)
     * @return array containing model settings
     */
    public function getSettingsForMultiSelect($values, $respondentId, $organizationId, $patientNr = null, $edit = true)
    {
        $concatter = new MUtil_Model_Type_ConcatenatedRow(self::FIELD_SEP, ' ', false);
        $multi     = explode(self::FIELD_SEP, $values);
        $output    = $concatter->getSettings();

        if ($edit) {
            $output['elementClass'] = 'MultiCheckbox';
        }
        $output['multiOptions'] = array_combine($multi, $multi);

        return $output;
    }

    /**
     * Setting function for single select
     *
     * @param string $values The content of the gtf_field_values field
     * @param int $respondentId When null $patientNr is required
     * @param int $organizationId
     * @param string $patientNr Optional for when $respondentId is null
     * @param boolean $edit True when editing, false for display (detailed is assumed to be true)
     * @return array containing model settings
     */
    public function getSettingsForSelect($values, $respondentId, $organizationId, $patientNr = null, $edit = true)
    {
        $multi = explode(self::FIELD_SEP, $values);
        $multi = array_combine($multi, $multi);

        if ($edit) {
            $output['elementClass'] = 'Select';
        }
        $output['multiOptions'] = $this->util->getTranslated()->getEmptyDropdownArray() + $multi;

        return $output;
    }

    /**
     * Catch all function for setting types
     *
     * @param string $type The type
     * @param string $values The content of the gtf_field_values field
     * @param int $respondentId When null $patientNr is required
     * @param int $organizationId
     * @param string $patientNr Optional for when $respondentId is null
     * @param boolean $edit True when editing, false for display (detailed is assumed to be true)
     * @return array containing model settings
     */
    public function getSettingsForType($type, $values, $respondentId, $organizationId, $patientNr = null, $edit = true)
    {
        if ($edit) {
            $output['elementClass'] = 'Text';
        }

        $output['size'] = 40;

        return $output;
    }

    /**
     * Function that checks the setup of this class/traight
     *
     * This function is not needed if the variables have been defined correctly in the
     * source for this object and theose variables have been applied.
     *
     * return @void
     */
    protected function initTranslateable()
    {
        if ($this->translateAdapter instanceof Zend_Translate_Adapter) {
            // OK
            return;
        }

        if ($this->translate instanceof Zend_Translate) {
            // Just one step
            $this->translateAdapter = $this->translate->getAdapter();
            return;
        }

        if ($this->translate instanceof Zend_Translate_Adapter) {
            // It does happen and if it is all we have
            $this->translateAdapter = $this->translate;
            return;
        }

        // Make sure there always is an adapter, even if it is fake.
        $this->translateAdapter = new MUtil_Translate_Adapter_Potemkin();
    }

    /**
     * Copy from Zend_Translate_Adapter
     *
     * Translates the given string using plural notations
     * Returns the translated string
     *
     * @see Zend_Locale
     * @param  string             $singular Singular translation string
     * @param  string             $plural   Plural translation string
     * @param  integer            $number   Number for detecting the correct plural
     * @param  string|Zend_Locale $locale   (Optional) Locale/Language to use, identical with
     *                                      locale identifier, @see Zend_Locale for more information
     * @return string
     */
    public function plural($singular, $plural, $number, $locale = null)
    {
        $args = func_get_args();
        return call_user_func_array(array($this->translateAdapter, 'plural'), $args);
    }
}
