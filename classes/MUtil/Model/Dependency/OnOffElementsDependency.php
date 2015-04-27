<?php

/**
 * Copyright (c) 2015, Erasmus MC
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
 * @package    MUtil
 * @subpackage Model_Dependency
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: OnOffElementsDependency.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

namespace MUtil\Model\Dependency;

/**
 * Set attributes when the dependent attribute is on - remove them otherwise
 *
 * @package    MUtil
 * @subpackage OnOffElementsDependency
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.7 27-apr-2015 18:50:11
 */
class OnOffElementsDependency extends DependencyAbstract
{
    /**
     *
     * @var array
     */
    protected $modeOff;

    /**
     *
     * @var array
     */
    protected $modeOn;

    /**
     * Should we just submit on click or use jQuery
     *
     * @var boolean
     */
    protected $submit = false;

    /**
     * Constructor checks any subclass set variables
     *
     * @param string $onElement The element that switches the other fields on or off
     * @param array|string $forElements The elements switched on or off
     * @param array|string $mode The values set ON when $onElement is true
     */
    public function __construct($onElement, $forElements, $mode = 'readonly')
    {
        $this->setDependsOn($onElement);

        if (is_array($mode)) {
            $this->modeOn = $mode;
        } else {
            $this->modeOn = array($mode => $mode);
        }
        $keys = array_keys($this->modeOn);
        $this->modeOff = array_fill_keys($keys, null);

        $this->_defaultEffects = array_combine($keys, $keys);

        $this->setEffecteds((array) $forElements);

        $this->addEffected($onElement, 'onchange');
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
        $dependsOns = $this->getDependsOn();
        $dependsOn  = reset($dependsOns);

        $effecteds = array_keys($this->getEffecteds());
        array_pop($effecteds); // Remove $dependsOn

        if (isset($context[$dependsOn]) && $context[$dependsOn]) {
            $value = $this->modeOn;
        } else {
            $value = $this->modeOff;
        }
        $output = array_fill_keys($effecteds, $value);

        if ($this->submit) {
            $javaScript = 'this.form.submit();';
        } else {
            $setScript = '';
            foreach ($this->modeOn as $key => $value) {
                if ($value) {
                    $valueOn = "e.setAttribute('$key', '$value');";
                } else {
                    $valueOn = "e.removeAttribute('$key');";
                }
                if (isset($this->modeOff[$key])) {
                    $val      = $this->modeOff[$key];
                    $valueOff = "e.setAttribute('$key', '$val');";
                } else {
                    $valueOff = "e.removeAttribute('$key');";
                }
                $setScript .= "if (this.value != 0) { $valueOn } else { $valueOff }; ";
            }
            $javaScript = '';
            foreach ($effecteds as $field) {
                $javaScript = "e = document.getElementById('$field'); " . $setScript;
            }
        }
        $output[$dependsOn]['onchange'] = $javaScript;

        return $output;
    }  // */
}
