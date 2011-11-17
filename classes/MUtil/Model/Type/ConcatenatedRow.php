<?php

/**
 * Copyright (c) 2011, Erasmus MC
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
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 *
 * @package    MUtil
 * @subpackage Model
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class MUtil_Model_Type_ConcatenatedRow
{
    protected $displaySeperator = ' ';

    protected $seperatorChar = ' ';

    protected $valuePad = true;

    public function __construct($seperatorChar = ' ', $displaySeperator = ' ', $valuePad = true)
    {
        $this->seperatorChar = substr($seperatorChar . ' ', 0, 1);
        $this->displaySeperator = $displaySeperator;
        $this->valuePad = $valuePad;
    }

    /**
     * If this field is saved as an array value, use
     *
     * @param MUtil_Model_ModelAbstract $model
     * @param string $name The field to set the seperator character
     * @return MUtil_Model_Type_ConcatenatedRow (continuation pattern)
     */
    public function apply(MUtil_Model_ModelAbstract $model, $name)
    {
        $model->set($name, 'formatFunction', array($this, 'format'));
        $model->setOnLoad($name, array($this, 'loadValue'));
        $model->setOnSave($name, array($this, 'saveValue'));

        return $this;
    }

    public function format($value)
    {
        // MUtil_Echo::track($value);
        if (is_array($value)) {
            return implode($this->displaySeperator, $value);
        } else {
            return $value;
        }
    }

    /**
     * A ModelAbstract->setOnSave() function that concatenates the
     * value if it is an array.
     *
     * @see Gems_Model_ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return array Of the values
     */
    public function loadValue($value, $isNew = false, $name = null, array $context = array())
    {
        // MUtil_Echo::track($value);
        if (! is_array($value)) {
            if ($this->valuePad) {
                $value = trim($value, $this->seperatorChar);
            }
            $value = explode($this->seperatorChar, $value);
        }
        // MUtil_Echo::track($value);

        return $value;
    }

    /**
     * A ModelAbstract->setOnSave() function that concatenates the
     * value if it is an array.
     *
     * @see Gems_Model_ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return string Of the values concatenated
     */
    public function saveValue($value, $isNew = false, $name = null, array $context = array())
    {
        if (is_array($value)) {
            $value = implode($this->seperatorChar, $value);

            if ($this->valuePad) {
                $value = $this->seperatorChar . $value . $this->seperatorChar;
            }
        }
        return $value;
    }
}
