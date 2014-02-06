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
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

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

        $this->setKeys(array(
            Gems_Model::FIELD_ID => 'gtf_id_field',
            MUtil_Model::REQUEST_ID => 'gtf_id_track',
            ));
        $this->setClearableKeys(array(Gems_Model::FIELD_ID => 'gtf_id_field'));
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

        if ($data['gtf_field_type'] === 'select' || $data['gtf_field_type'] === 'multiselect') {
            $this->set('gtf_field_values', 'label', $this->_('Values'),
                    'description', $this->_('Separate multiple values with a vertical bar (|)'),
                    'formatFunction', array($this, 'formatValues'));
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
