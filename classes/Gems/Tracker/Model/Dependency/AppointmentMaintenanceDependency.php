<?php

/**
 * Copyright (c) 2014, Erasmus MC
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
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Tracker_Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id: AppointmentMaintenanceDependency.php $
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
     * The settings array for those effecteds that don't have an effects array
     *
     * @var array
     */
    protected $_defaultEffects = array('description', 'elementClass', 'label', 'multiOptions', 'onchange', 'onclick',
        'filters', 'validators',
        );

    /**
     * Array of name => name of items dependency depends on.
     *
     * Can be overriden in sub class
     *
     * @var array Of name => name
     */
    protected $_dependentOn = array('gtf_filter_id', 'gtf_max_diff_exists', 'gtf_min_diff_length', 'gtf_create_track');

    /**
     * Array of name => array(setting => setting) of fields with settings changed by this dependency
     *
     * Can be overriden in sub class
     *
     * @var array of name => array(setting => setting)
     */
    protected $_effecteds = array(
        'gtf_id_order', 'htmlCalc', 'gtf_filter_id', 'gtf_min_diff_unit', 'gtf_min_diff_length',
        'gtf_max_diff_exists', 'gtf_max_diff_unit', 'gtf_max_diff_length', 'gtf_uniqueness',
        'gtf_create_track', 'gtf_create_wait_days',
        );

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
            'label'        => $this->_('Automatic link'),
            'description'  => $this->_('Automatically link an appointment when it passes this filter.'),
            'elementClass' => 'Select',
            'multiOptions' => $translated->getEmptyDropdownArray() + $filters,
            'onchange'     => 'this.form.submit();',
            );

        if ($context['gtf_filter_id']) {
            $periodUnits = $this->util->getTranslated()->getPeriodUnits();

            $output['gtf_min_diff_length'] = array(
                'label'             => $this->_('Minimal time difference'),
                'description'       => $this->_('Difference with the previous appointment or track start date, can be negative but not zero'),
                'elementClass'      => 'Text',
                'required'          => true,
                'size'              => 5,
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
                    'size'              => 5,
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
            $output['gtf_create_track'] = array(
                'elementClass' => 'Checkbox',
                'label'        => $this->_('Create track'),
                'onclick'      => 'this.form.submit();',
                );

            if ($context['gtf_create_track']) {
                $output['gtf_create_wait_days'] = array(
                    'label'       => $this->_('Days between tracks'),
                    'description' => $this->_('Any previous track must have an end date at least this many days in the past.'),
                    'elementClass' => 'Text',
                    );
            }
        }

        return $output;
    }

}
