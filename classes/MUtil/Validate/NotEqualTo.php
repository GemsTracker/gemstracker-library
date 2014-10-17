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
 * @package    MUtil
 * @subpackage Validate
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id: NotEqualTo.php $
 */

/**
 * Validates the a value is not the same as some other field value
 *
 * @package    MUtil
 * @subpackage Validate
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.4 17-okt-2014 15:23:24
 */
class MUtil_Validate_NotEqualTo extends Zend_Validate_Abstract
{
    /**
     * Error codes
     * @const string
     */
    const NOT_EQUAL_TO = 'notEqualTo';

    protected $_messageTemplates = array(
        self::NOT_EQUAL_TO => "Values may not be the same.",
    );

    /**
     * The field names against which to validate
     *
     * @var array
     */
    protected $fields;

    /**
     * An array containing field field specific error messages
     *
     * @var array fieldName => $message
     */
    protected $fieldMessages;

    /**
     * Sets validator options
     *
     * @param array|string $fields On or more values that this element should not have
     * @param string|array Optional different message or an array of messages containing field names, an int array value is set as a general message
     */
    public function __construct($fields, $message = null)
    {
        $this->fields = (array) $fields;

        if ($message) {
            foreach ((array) $message as $key => $msg) {
                if (in_array($key, $this->fields)) {
                    $this->fieldMessages[$key] = $msg;
                } else {
                    $this->setMessage($msg, self::NOT_EQUAL_TO);
                }
            }
        }
    }

    /**
     * Defined by Zend_Validate_Interface
     *
     * Returns true if and only if a token has been set and the provided value
     * matches that token.
     *
     * @param  mixed $value
     * @return boolean
     */
    public function isValid($value, $context = array())
    {
        if ($value) {
            foreach ($this->fields as $field) {
                if (isset($context[$field]) && ($value == $context[$field])) {

                    if (isset($this->fieldMessages[$field])) {
                        $this->setMessage($this->fieldMessages[$field], self::NOT_EQUAL_TO);
                    }

                    $this->_setValue((string) $value);
                    $this->_error(self::NOT_EQUAL_TO);
                    return false;
                }
            }
        }

        return true;
    }
}
