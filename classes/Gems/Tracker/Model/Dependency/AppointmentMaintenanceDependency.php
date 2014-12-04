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
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id: AppointmentMaintenanceDependency.php $
 */

namespace Gems\Tracker\Model\Dependency;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 16-okt-2014 18:30:05
 */
// class Gems_Tracker_Model_Dependency_AppointmentMaintenanceDependency extends MUtil_Model_Dependency_DependencyAbstract
class AppointmentMaintenanceDependency extends \MUtil_Model_Dependency_DependencyAbstract
{
    /**
     * Array of setting => setting of setting changed by this dependency
     *
     * The settings array for those effecteds that don't have an effects array
     *
     * @var array
     */
    protected $_defaultEffects = array('elementClass', 'label');

    /**
     * Array of name => name of items dependency depends on.
     *
     * Can be overriden in sub class
     *
     * @var array Of name => name
     */
    protected $_dependentOn = array('gtf_filter_id', 'gtf_create_track');

    /**
     * Array of name => array(setting => setting) of fields with settings changed by this dependency
     *
     * Can be overriden in sub class
     *
     * @var array of name => array(setting => setting)
     */
    protected $_effecteds = array('gtf_after_next', 'gtf_create_track', 'gtf_create_wait_days');

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
        $hideCreate = ! $context['gtf_filter_id'];
        if ($hideCreate) {
            $hideWait = true;
        } else {
            $hideWait = !$context['gtf_create_track'];
        }
        if ($hideCreate) {
            $output['gtf_after_next'] = array(
                'elementClass' => 'Hidden',
                'label'        => null,
                );
            $output['gtf_create_track'] = array(
                'elementClass' => 'Hidden',
                'label'        => null,
                );
        } else {
            $output['gtf_after_next'] = array(
                'elementClass' => 'Checkbox',
                );
            $output['gtf_create_track'] = array(
                'elementClass' => 'Checkbox',
                );
        }
        if ($hideWait) {
            $output['gtf_create_wait_days'] = array(
                'elementClass' => 'Hidden',
                'label'        => null,
                );
        } else {
            $output['gtf_create_wait_days'] = array(
                'elementClass' => 'Text',
                );
        }

        return $output;
    }

}
