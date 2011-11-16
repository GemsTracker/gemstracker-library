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
 * @subpackage Form
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: Sample.php 203 2011-07-07 12:51:32Z matijs $
 */

/**
 * This object allows you to supply a string value when the object expects an
 * array value, splitting the string along the valueSeperatorChar.
 *
 * Return this value as a string is not practical as that breaks the workings
 * of all Filters, Validators and Decorators.
 *
 * @package    MUtil
 * @subpackage Form
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class MUtil_Form_Element_MultiCheckbox extends Zend_Form_Element_MultiCheckbox
{
    /**
     * En/disables padding the value in separators. The default is true as this
     * simplifies search commands (and usually costs only 2 characters).
     *
     * @var boolean
     */
    protected $_valuePad = true;

    /**
     * The value seperator enables this control to accept a single string as value
     * and split it into an array.
     *
     * A null seperator means this class behaves as it's parent class and returns an
     * array value.
     *
     * @var string
     */
    protected $_valueSeperatorChar = null;

    /**
     * En/disables padding the value in separators. The default is true as this
     * simplifies search commands (and usually costs only 2 characters).
     *
     * @return boolean
     */
    public function getValuePad()
    {
        return $this->_valuePad;
    }

    /**
     * The value seperator enables this control to accept a single string as value
     * and split it into an array.
     *
     * A null seperator means this class behaves as it's parent class and returns an
     * array value.
     *
     * @return string
     */
    public function getValueSeperatorChar()
    {
        return $this->_valueSeperatorChar;
    }

    /**
     * Set element value
     *
     * @param  mixed $value
     * @return MUtil_Form_Element_MultiCheckbox (continuation pattern)
     */
    public function setValue($value)
    {
        if ((null !== $this->_valueSeperatorChar) && (! is_array($value))) {
            if ($this->_valuePad) {
                $value = trim($value, $this->_valueSeperatorChar);
            }
            $value = explode($this->_valueSeperatorChar, $value);
        }

        return parent::setValue($value);
    }

    /**
     * En/disables padding the value in separators. The default is true as this
     * simplifies search commands (and usually costs only 2 characters).
     *
     * @param boolean $value
     * @return MUtil_Form_Element_MultiCheckbox (continuation pattern)
     */
    public function setValuePad($value = true)
    {
        $this->_valuePad = $value;
        return $this;
    }

    /**
     * The value seperator enables this control to accept a single string as value
     * and split it into an array.
     *
     * A null seperator means this class behaves as it's parent class and returns an
     * array value.
     *
     * @param string $seperator
     * @return MUtil_Form_Element_MultiCheckbox (continuation pattern)
     */
    public function setValueSeperatorChar($seperator = ' ')
    {
        $this->_valueSeperatorChar = substr($seperator . ' ', 0, 1);
        return $this;
    }
}
