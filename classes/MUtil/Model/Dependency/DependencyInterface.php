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
 * @version    $Id: DependencyInterface .php 1748 2014-02-19 18:09:41Z matijsdejong $
 */

/**
 *
 * @package    MUtil
 * @subpackage Model_Dependency
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
interface MUtil_Model_Dependency_DependencyInterface
{
    // public function dependencyChanges($sourceName, $targetName, array $context, $isNew, MUtil_Model_ModelAbstract $model);

    /**
     * All string values passed to this function are added as a field the
     * dependency depends on.
     *
     * @param mixed $dependsOn
     * @return \MUtil_Model_Dependency_DependencyInterface (continuation pattern)
     */
    public function addDependsOn($dependsOn);

    /**
     * Adds which settings are effected by a value
     *
     *
     * @param string $effectedField A field name
     * @param mixed $effectedSettings A single setting or an array of settings
     * @return \MUtil_Model_Dependency_DependencyInterface (continuation pattern)
     */
    public function addEffected($effectedField, $effectedSettings);

    /**
     * Add to the fields effected by this dependency
     *
     * Do not override this function, override addEffected() instead
     *
     * @param array $effecteds Of values accepted by addEffected as paramter
     * @return \MUtil_Model_Dependency_DependencyInterface (continuation pattern)
     */
    public function addEffecteds(array $effecteds);

    /**
     * Does this dependency depends on this field?
     *
     * @param $name Field name
     * @return boolean
     */
    public function dependsOn();

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
    public function getChanges(array $context, $new);

    /**
     * Return the array of fields this dependecy depends on
     *
     * @return array name => name
     */
    public function getDependsOn();

    /**
     * Get the settings for this field effected by this dependency
     *
     * @param $name Field name
     * @return array of setting => setting of fields with settings for this $name changed by this dependency
     */
    public function getEffected($name);

    /**
     * Get the fields and their settings effected by by this dependency
     *
     * @return array of name => array(setting => setting) of fields with settings changed by this dependency
     */
    public function getEffecteds();

    /**
     * Is this field effected by this dependency?
     *
     * @param $name
     * @return boolean
     */
    public function isEffected($name);

    /**
     * All string values passed to this function are set as the fields the
     * dependency depends on.
     *
     * @param mixed $dependsOn
     * @return \MUtil_Model_Dependency_DependencyInterface (continuation pattern)
     */
    public function setDependsOn($dependsOn);

    /**
     * Add to the fields effected by this dependency
     *
     * @param array $effecteds Of values accepted by addEffected as paramter
     * @return \MUtil_Model_Dependency_DependencyInterface (continuation pattern)
     */
    public function setEffecteds(array $effecteds);
}
