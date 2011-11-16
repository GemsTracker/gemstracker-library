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
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: Sample.php 203 2011-07-07 12:51:32Z matijs $
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
class MUtil_Model_Save_ArraySaver
{
    protected $seperatorChar = ' ';

    protected $valuePad = true;

    public function __construct($seperatorChar = ' ', $valuePad = true)
    {
        $this->seperatorChar = substr($seperatorChar . ' ', 0, 1);
        $this->valuePad = $valuePad;
    }

    /**
     * If this field is saved as an array value, use
     *
     * @param MUtil_Model_ModelAbstract $model
     * @param string $name The field to set the seperator character
     * @param string $char
     * @param boolean $pad
     * @return MUtil_Model_ModelAbstract (continuation pattern)
     */
    public static function create(MUtil_Model_ModelAbstract $model, $name, $char = ' ', $pad = true)
    {
        $class = new self($char, $pad);

        $model->set($name, 'valueSeperatorChar', substr($char . ' ', 0, 1), 'valuePad', $pad);
        $model->setOnSave($name, array($class, 'saveValue'));

        return $class;
    }

    public function saveValue($name, $value, $isNew)
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
