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
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    MUtil
 * @subpackage Model_Dependency
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id: ReadonlyDependency.php 1748 2014-02-19 18:09:41Z matijsdejong $
 */

/**
 * A class for adding dependencies that turn readonly off in the model unless
 * one of the values the dependency depends on returns a true value.
 *
 * Example:
 * <code>
 * $model->addDependency('ReadOnlyDependency', array('cannot_edit'), $this->getColNames('label'));
 * </code>
 * Will set readonly=readonly for all fields with a label when cannot_edit returns true,
 * otherwise sets readonly=null
 *
 *
 * @package    MUtil
 * @subpackage Model_Dependency
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class MUtil_Model_Dependency_ReadonlyDependency extends MUtil_Model_Dependency_DependencyAbstract
{
    /**
     * The settings array for those effecteds that don't have an effects array
     *
     * @var array of setting => setting of setting changed by this dependency
     */
    protected $_defaultEffects = array('readonly');

    /**
     * Array for unsetting the readonly attributes
     *
     * @return array
     */
    protected function _getUneffecteds()
    {
        $output = array();

        foreach ($this->_effecteds as $key => $settings) {
            $output[$key] = array_fill_keys(array_keys($settings), null);
        }

        return $output;
    }

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
        foreach ($this->_dependentOn as $dependsOn) {
            if ($context[$dependsOn]) {
                return $this->_effecteds;
            }
        }

        return $this->_getUneffecteds();
    }
}
