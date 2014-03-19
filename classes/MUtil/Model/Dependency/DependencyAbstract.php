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
 * @version    $Id: DependencyAbstract .php 1748 2014-02-19 18:09:41Z matijsdejong $
 */

/**
 * A basic dependency implementation that all the housekeeping work,
 * but leaves the actual changes alone.
 *
 * @package    MUtil
 * @subpackage Model_Dependency
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
abstract class MUtil_Model_Dependency_DependencyAbstract extends MUtil_Translate_TranslateableAbstract
    implements MUtil_Model_Dependency_DependencyInterface
{
    /**
     * Can be overriden in sub class
     *
     * @var array Of name => name of items dependency depends on.
     */
    protected $_dependentOn = array();

    /**
     * Can be overriden in sub class
     *
     * @var array of name => array(setting => setting) of fields with settings changed by this dependency
     */
    protected $_effecteds = array();

    /**
     * Constructor checks any subclass set variables
     */
    public function __construct()
    {
        // Make sub class specified dependents confirm to system
        if ($this->_dependentOn) {
            $this->setDependsOn($this->_dependentOn);
        }
        // Make sub class specified effectds confirm to system
        if ($this->_effecteds) {
            $this->setEffecteds($this->_effecteds);
        }
    }

    /**
     * All string values passed to this function are added as a field the
     * dependency depends on.
     *
     * @param mixed $dependsOn
     * @return \MUtil_Model_Dependency_DependencyAbstract (continuation pattern)
     */
    public function addDependsOn($dependsOn)
    {
        $dependsOn = MUtil_Ra::flatten(func_get_args());

        foreach ($dependsOn as $dependOn) {
            $this->_dependentOn[$dependOn] = $dependOn;
        }

        return $this;
    }

    /**
     * Adds which settings are effected by a value
     *
     * Overrule this function, e.g. when a sub class changed a fixed setting,
     * but for diverse fields.
     *
     * @param string $effectedField A field name
     * @param mixed $effectedSettings A single setting or an array of settings
     * @return \MUtil_Model_Dependency_DependencyAbstract (continuation pattern)
     */
    public function addEffected($effectedField, $effectedSettings)
    {
        foreach ((array) $effectedSettings as $setting) {
            $this->_effecteds[$effectedField][$setting] = $setting;
        }

        return $this;
    }

    /**
     * Add to the fields effected by this dependency
     *
     * Do not override this function, override addEffected() instead
     *
     * @param array $effecteds Of values accepted by addEffected as paramter
     * @return \MUtil_Model_Dependency_DependencyAbstract (continuation pattern)
     */
    public final function addEffecteds(array $effecteds)
    {
        foreach ($effecteds as $effectedField => $effectedSettings) {
            return $this->addEffected($effectedField, $effectedSettings);
        }

        return $this;
    }

    /**
     * Does this dependency depends on this field?
     *
     * @param $name Field name
     * @return boolean
     */
    public function dependsOn()
    {
        return isset($this->_dependentOn[$name]);
    }

    // public function getChanges(array $context, $new);

    /**
     * Return the array of fields this dependecy depends on
     *
     * @return array name => name
     */
    public function getDependsOn()
    {
        return $this->_dependentOn;
    }

    /**
     * Get the settings for this field effected by this dependency
     *
     * @param $name Field name
     * @return array of setting => setting of fields with settings for this $name changed by this dependency
     */
    public function getEffected($name)
    {
        if (isset($this->_effecteds[$name])) {
            return $this->_effecteds[$name];
        }

        return array();
    }

    /**
     * Get the fields and their settings effected by by this dependency
     *
     * @return array of name => array(setting => setting) of fields with settings changed by this dependency
     */
    public function getEffecteds()
    {
        return $this->_effecteds;
    }

    /**
     * Is this field effected by this dependency?
     *
     * @param $name
     * @return boolean
     */
    public function isEffected($name)
    {
        return isset($this->_effecteds[$name]);
    }

    /**
     * All string values passed to this function are set as the fields the
     * dependency depends on.
     *
     * @param mixed $dependsOn
     * @return \MUtil_Model_Dependency_DependencyAbstract (continuation pattern)
     */
    public function setDependsOn($dependsOn)
    {
        $this->_dependentOn = array();

        return $this->addDependsOn(func_get_args());
    }

    /**
     * Add to the fields effected by this dependency
     *
     * Do not override this function, override addEffected() instead
     *
     * @param array $effecteds Of values accepted by addEffected as paramter
     * @return \MUtil_Model_Dependency_DependencyAbstract (continuation pattern)
     */
    public final function setEffecteds(array $effecteds)
    {
        $this->_effecteds = array();

        return $this->addEffecteds($effecteds);
    }
}
