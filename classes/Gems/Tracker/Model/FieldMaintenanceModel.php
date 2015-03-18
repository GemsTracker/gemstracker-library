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

namespace Gems\Tracker\Model;

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
class FieldMaintenanceModel extends \MUtil_Model_UnionModel
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
     * Should a type dependency be added uin _processRowAfterLoad?
     *
     * @var boolean
     */
    protected $_addLoadDependency = false;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * The field types that have a dependency
     *
     * @var array fieldType => dependency class name (without path elements)
     */
    protected $dependencies = array(
        'activity'    => 'FromAppointmentsMaintenanceDependency',
        'appointment' => 'AppointmentMaintenanceDependency',
        'caretaker'   => 'FromAppointmentsMaintenanceDependency',
        'date'        => 'FromAppointmentsMaintenanceDependency',
        'datetime'    => 'FromAppointmentsMaintenanceDependency',
        'location'    => 'FromAppointmentsMaintenanceDependency',
        'multiselect' => 'ValuesMaintenanceDependency',
        'select'      => 'ValuesMaintenanceDependency',
        'procedure'   => 'FromAppointmentsMaintenanceDependency',
        );

    /**
     *
     * @var \Gems_Tracker
     */
    protected $tracker;

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
     * Process on load functions and dependencies
     *
     * @see addDependency()
     * @see setOnLoad()
     *
     * @param array $row The row values to load
     * @param boolean $new True when it is a new item not saved in the model
     * @param boolean $isPost True when passing on post data
     * @param array $transformColumns
     * @return array The possibly adapted array of values
     */
    protected function _processRowAfterLoad(array $row, $new = false, $isPost = false, &$transformColumns = array())
    {
        if ($this->_addLoadDependency) {
            // Display of data field
            if (! (isset($row['gtf_field_type']) && $row['gtf_field_type'])) {
                $row['gtf_field_type'] = $this->getFieldType($row);
            }
            // assert: $row['gtf_field_type'] is now always filled.

            if (! isset($row[$this->_modelField])) {
                $row[$this->_modelField] = $this->getModelNameForRow($row);
            }

            // Now add the type specific dependency (if any)
            $class = $this->getTypeDependencyClass($row['gtf_field_type']);
            if ($class) {
                $dependency = $this->tracker->createTrackClass($class, $row['gtf_id_track']);
                $this->addDependency($dependency, null, null, 'row');
            }
        }

        return parent::_processRowAfterLoad($row, $new, $isPost, $transformColumns);
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
     * @return \Gems\Tracker\Model\FieldMaintenanceModel (continuation pattern)
     */
    public function applyBrowseSettings()
    {
        $this->resetOrder();

        $yesNo = $this->util->getTranslated()->getYesNo();
        $types = $this->getFieldTypes();
        asort($types);

        $this->set('gtf_id_track'); // Set order
        $this->set('gtf_field_name',    'label', $this->_('Name'));
        $this->set('gtf_id_order',      'label', $this->_('Order'),
                'description', $this->_('The display and processing order of the fields.')
                );
        $this->set('gtf_field_type',    'label', $this->_('Type'),
                'multiOptions', $types,
                'default', 'text'
                );
        $this->set('gtf_field_values'); // Set order
        $this->set('gtf_field_description'); // Set order
        $this->set('gtf_field_code',    'label', $this->_('Code Name'),
                'description', $this->_('Optional extra name to link the field to program code.')
                );
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

        $this->set('gtf_filter_id'); // Set order
        $this->set('gtf_after_next'); // Set order
        $this->set('gtf_uniqueness'); // Set order
        $this->set('gtf_create_track', 'label', $this->_('Create track'),
                'description', $this->_('Create a track if the respondent does not have a track where this field is empty.'),
                'multiOptions', $yesNo
                );
        $this->set('gtf_create_wait_days'); // Set order

        return $this;
    }

    /**
     * Set those settings needed for the detailed display
     *
     * @return \Gems\Tracker\Model\FieldMaintenanceModel (continuation pattern)
     */
    public function applyDetailSettings()
    {
        $this->applyBrowseSettings();

        $this->_addLoadDependency = true;

        $this->set('gtf_id_track',          'label', $this->_('Track'),
                'multiOptions', $this->util->getTrackData()->getAllTracks()
                );
        $this->set('gtf_field_description', 'label', $this->_('Description'),
                'description', $this->_('Optional extra description to show the user.')
                );

        // Clean up data always show in browse view, but not always in detail views
        $this->set('gtf_calculate_using', 'label', null, 'formatFunction', null);

        // But do always transform gtf_calculate_using on load and save
        // as otherwise we might not be sure what to do
        $contact = new \MUtil_Model_Type_ConcatenatedRow(self::FIELD_SEP, '; ', false);
        $contact->apply($this, 'gtf_calculate_using');

        // Clean up data always show in browse view, but not always in detail views
        $this->set('gtf_create_track',    'label', null);
    }

    /**
     * Set those values needed for editing
     *
     * @return \Gems\Tracker\Model\FieldMaintenanceModel (continuation pattern)
     */
    public function applyEditSettings()
    {
        $this->applyDetailSettings();

        $this->set('gtf_id_field',          'elementClass', 'Hidden');
        $this->set('gtf_id_track',          'elementClass', 'Exhibitor');
        $this->set('gtf_field_type',        'elementClass', 'Exhibitor');

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
        $this->set('gtf_field_values',      'elementClass', 'Hidden');

        $this->set('gtf_to_track_info',     'elementClass', 'CheckBox');
        $this->set('gtf_required',          'elementClass', 'CheckBox');
        $this->set('gtf_readonly',          'elementClass', 'CheckBox');

        $this->set('gtf_filter_id',         'elementClass', 'Hidden');
        $this->set('gtf_after_next',        'elementClass', 'Hidden');
        $this->set('gtf_uniqueness',        'elementClass', 'Hidden');
        $this->set('gtf_create_track',      'elementClass', 'Hidden');
        $this->set('gtf_create_wait_days',  'elementClass', 'Hidden');

        $class      = 'Model\\Dependency\\FieldTypeChangeableDependency';
        $dependency = $this->tracker->createTrackClass($class, $this->_modelField);
        $this->addDependency($dependency);
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
     * Get the type from the row in case it was not set
     *
     * @param array $row Loaded row
     * @return string Data type for the row
     */
    protected function getFieldType(array &$row)
    {
        if (isset($row[$this->_modelField]) && ($row[$this->_modelField] === self::APPOINTMENTS_NAME)) {
            return 'appointment';
        }

        if (isset($row['gtf_id_field']) && $row['gtf_id_field']) {
            $row[\Gems_Model::FIELD_ID] = $row['gtf_id_field'];
        }

        if (isset($row[\Gems_Model::FIELD_ID])) {
            return $this->db->fetchOne(
                    "SELECT gtf_field_type FROM gems__track_fields WHERE gtf_id_field = ?",
                    $row[\Gems_Model::FIELD_ID]
                    );
        }

        if (! $this->has('gtf_field_type', 'default')) {
            $this->set('gtf_field_type', 'default', 'text');
        }
        return $this->get('gtf_field_type', 'default');
    }

    /**
     * The list of field types
     *
     * @return array of storage name => label
     */
    public function getFieldTypes()
    {
        $output = array(
            'activity'    => $this->_('Activity'),
            'appointment' => $this->_('Appointment'),
            'caretaker'   => $this->_('Caretaker'),
            'date'        => $this->_('Date'),
            'text'        => $this->_('Free text'),
            'location'    => $this->_('Location'),
            'datetime'    => $this->_('Moment in time'),
            'procedure'   => $this->_('Procedure'),
            'relation'    => $this->_('Relation'),
            'select'      => $this->_('Select one'),
            'multiselect' => $this->_('Select multiple'),
            );

        asort($output);
        return $output;
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
     * Get the dependency class name (if any)
     *
     * @param string $fieldType
     * @return string Classname including Model\Dependency\ part
     */
    public function getTypeDependencyClass($fieldType)
    {
        if (isset($this->dependencies[$fieldType]) && $this->dependencies[$fieldType]) {
            return 'Model\\Dependency\\' . $this->dependencies[$fieldType];
        }
    }

    /**
     * Does the model have a dependencies?
     *
     * @return boolean
     */
    public function hasDependencies()
    {
        return $this->_addLoadDependency || parent::hasDependencies();
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
     * Returns an array containing the first requested item.
     *
     * @param mixed $filter True to use the stored filter, array to specify a different filteloa
     * @param mixed $sort True to use the stored sort, array to specify a different sort
     * @param boolean $loadDependencies When true the row dependencies are loaded
     * @return array An array or false
     */
    public function loadFirst($filter = true, $sort = true, $loadDependencies = true)
    {
        // Needed as the default order otherwise triggers the type dependency
        $oldDep = $this->_addLoadDependency;
        $this->_addLoadDependency = $loadDependencies;

        $output = parent::loadFirst();

        $this->_addLoadDependency = $oldDep;

        return $output;
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
