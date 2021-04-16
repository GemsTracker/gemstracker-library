<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker_Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Model\Dependency;

use MUtil\Model\Dependency\DependencyAbstract;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker_Model
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 16-okt-2014 18:30:05
 */
class AppointmentMaintenanceDependency extends DependencyAbstract
{
    /**
     * Array of setting => setting of setting changed by this dependency
     *
     * The settings array for those effected items that don't have an effects array
     *
     * @var array
     */
    protected $_defaultEffects = array('description', 'elementClass', 'label', 'multiOptions', 'onchange', 'onclick',
        'filters', 'validators',
        );

    /**
     * Array of name => name of items dependency depends on.
     *
     * Can be overridden in sub class
     *
     * @var array Of name => name
     */
    protected $_dependentOn = array('gtf_id_track', 'gtf_id_order', 'gtf_filter_id', 'gtf_max_diff_exists', 'gtf_min_diff_length', 'gtf_create_track');

    /**
     * Array of name => array(setting => setting) of fields with settings changed by this dependency
     *
     * Can be overridden in sub class
     *
     * @var array of name => array(setting => setting)
     */
    protected $_effecteds = array(
        'gtf_id_order', 'htmlCalc', 'gtf_filter_id', 'gtf_min_diff_unit', 'gtf_min_diff_length',
        'gtf_max_diff_exists', 'gtf_max_diff_unit', 'gtf_max_diff_length', 'htmlCreate', 'gtf_uniqueness',
        'gtf_create_track', 'gtf_create_wait_days',
        );

    /**
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
     * @var \Gems_Menu
     */
    protected $menu;

    /**
     *
     * @var \Gems_Util
     */
    protected $util;

    /**
     * Returns the changes that must be made in an array consisting of
     *
     * <code>
     * array(
     *  field1 => array(setting1 => $value1, setting2 => $value2, ...),
     *  field2 => array(setting3 => $value3, setting4 => $value4, ...),
     * </code>
     *
     * By using [] array notation in the setting name you can append to existing
     * values.
     *
     * Use the setting 'value' to change a value in the original data.
     *
     * When a 'model' setting is set, the workings cascade.
     *
     * @param array $context The current data this object is dependent on
     * @param boolean $new True when the item is a new record not yet saved
     * @return array name => array(setting => value)
     */
    public function getChanges(array $context, $new)
    {
        // Only change anything when there are filters
        $filters = $this->loader->getAgenda()->getFilterList();

        if (! $filters) {
            return array();
        }

        // Load utility
        $translated = $this->util->getTranslated();

        $output['gtf_id_order'] =array(
            'description' => $this->_('The display and processing order of the fields.') . "\n" .
            $this->_('When using automatic filters the fields are ALWAYS filled with appointments in ascending order.'),
            );

        $output['htmlCalc'] = array(
            'label'        => ' ',
            'elementClass' => 'Exhibitor',
        );
        $output['gtf_filter_id'] = array(
            'label'          => $this->_('Appointment filter'),
            'description'    => $this->_('Automatically link an appointment when it passes this filter.'),
            'elementClass'   => 'Select',
            'formatFunction' => [$this, 'showFilter', true],
            'multiOptions'   => $translated->getEmptyDropdownArray() + $filters,
            'onchange'       => 'this.form.submit();',
            );

        if ($context['gtf_filter_id']) {
            $periodUnits = $this->util->getTranslated()->getPeriodUnits();
            
            if (isset($context['gtf_id_track'], $context['gtf_id_order'])) {
                $previous = $this->db->fetchRow(
                    "SELECT * FROM gems__track_appointments WHERE gtap_id_track = ? AND gtap_id_order < ? ORDER BY gtap_id_order DESC LIMIT 1",
                    [$context['gtf_id_track'], $context['gtf_id_order']]
                );
                // \MUtil_Echo::track($previous);
                if ($previous) {
                    $diffDescription = sprintf(
                        $this->_("Difference with the previous '%s' appointment (order %d), can be negative but not zero"),
                        $previous['gtap_field_name'],
                        $previous['gtap_id_order']
                    );
                } else {
                    $diffDescription = $this->_('Difference with the track start date, can be negative but not zero');                
                }
            } else {
                $diffDescription = $this->_('Difference with the previous appointment or track start date, can be negative but not zero');
            }

            $output['gtf_min_diff_length'] = array(
                'label'             => $this->_('Minimal time difference'),
                'description'       => $diffDescription,
                'elementClass'      => 'Text',
                'required'          => true,
                // 'size'              => 5, // Causes trouble during save
                'filters[int]'      => 'Int',
                'validators[isnot]' => new \MUtil_Validate_IsNot(0, $this->_('This value may not be zero!')),
                );
            $output['gtf_min_diff_unit'] = array(
                'label'        => $this->_('Minimal difference unit'),
                'elementClass' => 'Select',
                'multiOptions' => $periodUnits,
                );
            $output['gtf_max_diff_exists'] = array(
                'label'        => $this->_('Set a maximum time difference'),
                'elementClass' => 'Checkbox',
                'onclick'      => 'this.form.submit();',
            );
            if ($context['gtf_max_diff_exists']) {
                $output['gtf_max_diff_length'] = array(
                    'label'             => $this->_('Maximum time difference'),
                    'elementClass'      => 'Text',
                    'required'          => false,
                    // 'size'              => 5, // Causes trouble during save
                    'filters[int]'      => 'Int',
                    );
                if ($context['gtf_min_diff_length'] < 0) {
                    $output['gtf_max_diff_length']['description'] = $this->_(
                            'Must be negative, just like the minimal difference.'
                            );
                    $output['gtf_max_diff_length']['validators[lt]'] = new \Zend_Validate_LessThan(0);
                } else {
                    $output['gtf_max_diff_length']['description'] = $this->_(
                            'Must be positive, just like the minimal difference.'
                            );
                    $output['gtf_max_diff_length']['validators[gt]'] = new \Zend_Validate_GreaterThan(0);
                }
                $output['gtf_max_diff_unit'] = array(
                    'label'        => $this->_('Maximum difference unit'),
                    'elementClass' => 'Select',
                    'multiOptions' => $periodUnits,
                    );
            }
//            $output['gtf_after_next'] = array(
//                'label'        => $this->_('Link ascending'),
//                'description'  => $this->_('Automatically linked appointments are added in ascending (or otherwise descending) order; starting with the track start date.'),
//                'elementClass' => 'Checkbox',
//                'multiOptions' => $translated->getYesNo(),
//                );
            $output['gtf_uniqueness'] = array(
                'label'        => $this->_('Link unique'),
                'description'  => $this->_('Can one appointment be used in multiple fields?'),
                'elementClass' => 'Radio',
                'multiOptions' => array(
                    0 => $this->_('No: repeatedly linked appointments are allowed.'),
                    1 => $this->_('Track instances may link only once to an appointment.'),
                    2 => $this->_('Tracks of this type may link only once to an appointment.'),
    //                 3 => $this->_('Appointment may not be used in any other track.'),
                    ),
                );
            $output['htmlCreate'] = array(
                'label'        => ' ',
                'elementClass' => 'Exhibitor',
            );
            $output['gtf_create_track'] = $this->loader->getAgenda()->getTrackCreateElement();
        }

        $label = false;
        $description = false;
        if ($context['gtf_create_track']) {
            switch ($context['gtf_create_track']) {
                case 1:
                    $label = $this->_('End date difference');
                    $description = $this->_('Any previous track must be closed and have an end date at least this many days in the past.');
                    break;
                case 2:
                    $label = $this->_('End date difference');
                    $description = $this->_('Any previous track must have an end date at least this many days in the past.');
                    break;
                case 4:
                    $label = $this->_('Start date difference');
                    $description = $this->_('Any previous track must have an start date at least this many days in the past.');
                    break;
                case 5:
                    break;

            }
        }
        if ($label && $description) {
            $output['gtf_create_wait_days'] = array(
                'label'        => $label,
                'description'  => $description,
                'elementClass' => 'Text',
                );
        } else {
            unset($output['gtf_create_wait_days']);
        }

        return $output;
    }

    /**
     * Show filter as link
     *
     * @param string $value
     * @param int $raw
     */
    public function showFilter($value, $raw)
    {
        $menuFilter = $this->menu->findAllowedController('agenda-filter', 'show');

        if (! $menuFilter) {
            return $value;
        }

        return \MUtil_Html_AElement::a($menuFilter->toHRefAttribute([\MUtil_Model::REQUEST_ID => $raw]), $value);
    }
}
