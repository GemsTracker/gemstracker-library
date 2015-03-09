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

use Gems\Tracker\Model\Dependency\AppointmentMaintenanceDependency;
use Gems\Tracker\Engine\FieldsDefinition;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class Gems_Tracker_Model_FieldMaintenanceModel extends \MUtil_Model_UnionModel
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
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * The fields that can be calculated using an appointment as input
     *
     * @var array
     */
    protected $fromAppointments = array('caretaker', 'location', 'activity', 'procedure');

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var \Zend_Translate
     */
    protected $translate;

    /**
     *
     * @var \Zend_Translate_Adapter
     */
    protected $translateAdapter;

    /**
     *
     * @var \Gems_Util
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

        $model = new \MUtil_Model_TableModel('gems__track_fields');
        \Gems_Model::setChangeFieldsByPrefix($model, 'gtf');
        $this->addUnionModel($model, null, self::FIELDS_NAME);

        $model = new \MUtil_Model_TableModel('gems__track_appointments');
        \Gems_Model::setChangeFieldsByPrefix($model, 'gtap');

        $map = $model->getItemsOrdered();
        $map = array_combine($map, str_replace('gtap_', 'gtf_', $map));
        $map['gtap_id_app_field'] = 'gtf_id_field';

        $this->addUnionModel($model, $map, self::APPOINTMENTS_NAME);

        $model->addColumn(new \Zend_Db_Expr("'appointment'"), 'gtf_field_type');
        $model->addColumn(new \Zend_Db_Expr("NULL"), 'gtf_field_values');
        $model->addColumn(new \Zend_Db_Expr("NULL"), 'gtf_calculate_using');

        $this->setKeys(array(
            \Gems_Model::FIELD_ID => 'gtf_id_field',
            \MUtil_Model::REQUEST_ID => 'gtf_id_track',
            ));
        $this->setClearableKeys(array(\Gems_Model::FIELD_ID => 'gtf_id_field'));
        $this->setSort(array('gtf_id_order' => SORT_ASC));
    }

    /**
     * Copy from \Zend_Translate_Adapter
     *
     * Translates the given string
     * returns the translation
     *
     * @param  string             $text   Translation string
     * @param  string|\Zend_Locale $locale (optional) Locale/Language to use, identical with locale
     *                                    identifier, @see \Zend_Locale for more information
     * @return string
     */
    public function _($text, $locale = null)
    {
        return $this->translateAdapter->_($text, $locale);
    }

    /**
     * Get an options list of all the appointment fields
     *
     * @param type $trackId
     * @return array field_id => field_name
     */
    protected function _loadAppointments($trackId)
    {
        $appFields = $this->db->fetchPairs("
            SELECT gtap_id_app_field, gtap_field_name
                FROM gems__track_appointments
                WHERE gtap_id_track = ?
                ORDER BY gtap_id_order", $trackId);

        $options = array();

        if ($appFields) {
            foreach ($appFields as $id => $label) {
                $key = FieldsDefinition::makeKey(self::APPOINTMENTS_NAME, $id);
                $options[$key] = $label;
            }
        }

        return $options;
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
     * @return \Gems_Tracker_Model_FieldMaintenanceModel (continuation pattern)
     */
    public function applyBrowseSettings()
    {
        $this->resetOrder();

        $yesNo = $this->util->getTranslated()->getYesNo();
        $types = $this->getFieldTypes();
        asort($types);

        $this->set('gtf_field_name',    'label', $this->_('Name'));
        $this->set('gtf_id_order',      'label', $this->_('Order'),
                'description', $this->_('The display and processing order of the fields.')
                );
        $this->set('gtf_field_type',    'label', $this->_('Type'),
                'multiOptions', $types,
                'default', 'text'
                );
        $this->set('gtf_field_code',    'label', $this->_('Code Name'),
                'description', $this->_('Optional extra name to link the field to program code.'));
        $this->set('gtf_to_track_info', 'label', $this->_('In description'),
                'description', $this->_('Add this field to the track description'),
                'multiOptions', $yesNo
                );
        $this->set('gtf_required',      'label', $this->_('Required'),
                'multiOptions', $yesNo
                );
        $this->set('gtf_readonly',      'label', $this->_('Readonly'),
                'multiOptions', $yesNo,
                'description', $this->_('Check this box if this field is always set by code instead of the user.')
                );

        $this->set('gtf_calculate_using', 'label', $this->_('Calculate using'),
                'description', $this->_('Automatically calculate this field using other fields'),
                'formatFunction', array($this, 'countCalculationSources')
                );
        $this->set('gtf_create_track', 'label', $this->_('Create track'),
                'description', $this->_('Create a track if the respondent does not have a track where this field is empty.'),
                'multiOptions', $yesNo
                );

        return $this;
    }

    /**
     * Set those settings needed for the detailed display
     *
     * @param int $trackId The current track id
     * @param array $data The currently known data
     * @return \Gems_Tracker_Model_FieldMaintenanceModel (continuation pattern)
     */
    public function applyDetailSettings($trackId, array &$data)
    {
        $this->applyBrowseSettings();

        $this->set('gtf_id_track',          'label', $this->_('Track'),
                'multiOptions', $this->util->getTrackData()->getAllTracks(),
                'order', 2
                );
        $order = $this->getOrder('gtf_field_type') + 1;
        $this->set('gtf_field_description', 'label', $this->_('Description'),
                'description', $this->_('Optional extra description to show the user.'),
                'order', $order++
                );

        // Display of data field
        if (! (isset($data['gtf_field_type']) && $data['gtf_field_type'])) {
            if (isset($data[$this->_modelField]) && ($data[$this->_modelField] === self::APPOINTMENTS_NAME)) {
                $data['gtf_field_type'] = 'appointment';
            } else {
                if (isset($data['gtf_id_field']) && $data['gtf_id_field']) {
                    $data[\Gems_Model::FIELD_ID] = $data['gtf_id_field'];
                }
                if (isset($data[\Gems_Model::FIELD_ID])) {
                    $data['gtf_field_type'] = $this->db->fetchOne(
                            "SELECT gtf_field_type FROM gems__track_fields WHERE gtf_id_field = ?",
                            $data[\Gems_Model::FIELD_ID]
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
                    'formatFunction', array($this, 'formatValues'),
                    'order', $order++
                    );
        }

        // Clean up never used
        $this->set('gtf_calculate_using', 'label', $this->_('Calculate from'),
                'formatFunction', null
                );
        if ($trackId) { // && in_array($data['gtf_field_type'], $this->fromAppointments)) {
            $method = 'loadOptionsFor' . ucfirst($data['gtf_field_type']);
            if (!method_exists($this, $method)) {
                if (in_array($data['gtf_field_type'], $this->fromAppointments)) {
                    $method = '_loadAppointments';
                } else {
                    $method = false;
                }
            }

            if ($method) {
                $options = call_user_func(array($this, $method), $trackId);

                if ($options) {
                    $this->set('gtf_calculate_using',
                            'elementClass', 'MultiCheckbox',
                            'multiOptions', $options
                            );

                    $contact = new \MUtil_Model_Type_ConcatenatedRow(self::FIELD_SEP, '; ', false);
                    $contact->apply($this, 'gtf_calculate_using');
                }
            }
        }
        if (! $this->has('gtf_calculate_using', 'elementClass')) {
            $this->del('gtf_calculate_using', 'label', 'description');
        }

        if ('appointment' == $data['gtf_field_type']) {
            $filters = $this->loader->getAgenda()->getFilterList();

            if ($filters) {
                $translated = $this->util->getTranslated();

                $this->set('gtf_id_order', 'description',
                        $this->get('gtf_id_order', 'description') . "\n" .
                        $this->_('When using automatic filters the fields are ALWAYS filled with appointments in ascending order.')
                        );

                $this->set('gtf_filter_id', 'label', $this->_('Automatic link'),
                        'description', $this->_('Automatically link an appointment when it passes this filter.'),
                        'multiOptions', $translated->getEmptyDropdownArray() + $filters,
                        'onchange', 'this.form.submit();'
                        );
                $this->set('gtf_after_next', 'label', $this->_('Link ascending'),
                        'description', $this->_('Automatically linked appointments are added in ascending (or otherwise descending) order; starting with the track start date.'),
                        'multiOptions', $translated->getYesNo(),
                        'order', $this->getOrder('gtf_filter_id') + 1
                        );
                $this->set('gtf_uniqueness', 'label', $this->_('Link unique'),
                        'description', $this->_('Can one appointment be used in multiple fields?'),
                        'multiOptions', array(
                            0 => $this->_('No: repeatedly linked appointments are allowed.'),
                            1 => $this->_('Track instances may link only once to an appointment.'),
                            2 => $this->_('Tracks of this type may link only once to an appointment.'),
//                            3 => $this->_('Appointment may not be used in any other track.'),
                        ),
                        'order', $this->getOrder('gtf_filter_id') + 1
                        );
                $this->set('gtf_create_track',
                        'onclick', 'this.form.submit();',
						'order', $this->getOrder('gtf_filter_id') + 2
                        );
                $this->set('gtf_create_wait_days',
                        'label', $this->_('Days between tracks'),
                        'description', $this->_('Any previous track must have an end date at least this many days in the past.')
                        );

                // $this->addDependency(new \Gems_Tracker_Model_Dependency_AppointmentMaintenanceDependency());
                // $this->addDependency(new \Gems\Tracker\Model\Dependency\AppointmentMaintenanceDependency());
                $this->addDependency(new AppointmentMaintenanceDependency());
            }
        }
        if (! $this->has('gtf_create_track', 'onclick')) {
            $this->del('gtf_create_track', 'label', 'description');
        }
    }

    /**
     * Set those values needed for editing
     *
     * @param int $trackId The current track id
     * @param array $data The currently known data
     * @return \Gems_Tracker_Model_FieldMaintenanceModel (continuation pattern)
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
        if ($sql && isset($data[\Gems_Model::FIELD_ID])) {
            $noSubChange = $this->db->fetchOne($sql, $data[\Gems_Model::FIELD_ID]);
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
                'validators[gt]', new \Zend_Validate_GreaterThan(0)
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
        $this->set('gtf_to_track_info',     'elementClass', 'CheckBox');
        $this->set('gtf_required',          'elementClass', 'CheckBox');
        $this->set('gtf_readonly',          'elementClass', 'CheckBox');
    }

    /**
     * A format function
     *
     * @return A string describing the contentn
     */
    public function countCalculationSources($value)
    {
        if (! $value) {
            return null;
        }

        $count = substr_count($value, '|') + 1;

        return sprintf($this->plural('%d field', '%d fields', $count), $count);
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
        return new \MUtil_Html_Sequence(array('glue' => '<br/>'), explode('|', $values));
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
            'activity'    => $this->_('Activity'),
            'procedure'   => $this->_('Procedure'),
            );
    }

    /**
     * Get the name of the union model that should be used for this row.
     *
     * @param array $row
     * @return string
     */
    public function getModelNameForRow(array $row)
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
        if ($this->translateAdapter instanceof \Zend_Translate_Adapter) {
            // OK
            return;
        }

        if ($this->translate instanceof \Zend_Translate) {
            // Just one step
            $this->translateAdapter = $this->translate->getAdapter();
            return;
        }

        if ($this->translate instanceof \Zend_Translate_Adapter) {
            // It does happen and if it is all we have
            $this->translateAdapter = $this->translate;
            return;
        }

        // Make sure there always is an adapter, even if it is fake.
        $this->translateAdapter = new \MUtil_Translate_Adapter_Potemkin();
    }

    /**
     * Copy from \Zend_Translate_Adapter
     *
     * Translates the given string using plural notations
     * Returns the translated string
     *
     * @see \Zend_Locale
     * @param  string             $singular Singular translation string
     * @param  string             $plural   Plural translation string
     * @param  integer            $number   Number for detecting the correct plural
     * @param  string|\Zend_Locale $locale   (Optional) Locale/Language to use, identical with
     *                                      locale identifier, @see \Zend_Locale for more information
     * @return string
     */
    public function plural($singular, $plural, $number, $locale = null)
    {
        $args = func_get_args();
        return call_user_func_array(array($this->translateAdapter, 'plural'), $args);
    }
}
