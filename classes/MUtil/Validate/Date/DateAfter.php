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
 * @subpackage Validate
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 * @package    MUtil
 * @subpackage Validate
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.0
 */
class MUtil_Validate_Date_DateAfter extends \MUtil_Validate_Date_DateAbstract
{
    /**
     * Error constants
     */
    const NOT_AFTER = 'notAfter';
    const NO_VALIDFROM = 'noValidFrom';

    /**
     * @var array Message templates
     */
    protected $_messageTemplates = array(
        self::NOT_AFTER => "Date should be '%dateAfter%' or later.",
        self::NO_VALIDFROM => "Should be empty if valid from date is not set."
    );

    /**
     * @var array
     */
    protected $_messageVariables = array(
        'dateAfter' => '_afterValue',
    );

    protected $_afterDate;
    protected $_afterValue;

    public function __construct($afterDate = null, $format = 'dd-MM-yyyy')
    {
        parent::__construct($format);
        $this->_afterDate = $afterDate;
    }

    /**
     * Returns true if and only if $value meets the validation requirements
     *
     * If $value fails validation, then this method returns false, and
     * getMessages() will return an array of messages that explain why the
     * validation failed.
     *
     * @param  mixed $value
     * @return boolean
     * @throws \Zend_Valid_Exception If validation of $value is impossible
     */
    public function isValid($value, $context = null)
    {
        if (null === $this->_afterDate) {
            $this->_afterDate = new \Zend_Date();
        }

        if ($this->_afterDate instanceof \Zend_Date) {
            $after = $this->_afterDate;
        } elseif (isset($context[$this->_afterDate])) {
            if (empty($context[$this->_afterDate])) {
                $this->_error(self::NO_VALIDFROM);
                return false;
            }

            $after = new \Zend_Date($context[$this->_afterDate], $this->getDateFormat());
        } elseif (\Zend_Date::isDate($this->_afterDate, $this->getDateFormat())) {
            $after = new \Zend_Date($this->_afterDate, $this->getDateFormat());
        } else {
            // No date specified, return true
            return true;
        }
        $this->_afterValue = $after->toString($this->getDateFormat());

        $check = new \Zend_Date($value, $this->getDateFormat());

        if ($check->isEarlier($after)) {
            $this->_error(self::NOT_AFTER);
            return false;
        }

        return true;
    }
}
