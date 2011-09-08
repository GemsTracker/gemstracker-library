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
 */

/**
 * File description of TokenValidator
 *
 * @author Matijs de Jong <mjong@magnafacta.nl>
 * @since 1.4
 * @version 1.4
 * @package Gems
 * @subpackage Tracker 
 */

/**
 * Class description of TokenValidator
 *
 * @author Matijs de Jong <mjong@magnafacta.nl>
 * @package Gems
 * @subpackage Tracker 
 */
class Gems_Tracker_Token_TokenValidator extends Zend_Validate_Abstract
{
    /**
     * Error constants
     */
    const NOT_TOKEN_FORMAT      = 'notFormat';
    const TOKEN_DOES_NOT_EXIST  = 'notThere';
    const TOKEN_NO_LONGER_VALID = 'noLongerValid';
    const TOKEN_NOT_YET_VALID   = 'notYetValid';
    
    /**
     * @var array Message templates
     */
    protected $_messageTemplates = array(
        self::NOT_TOKEN_FORMAT      => 'Not a valid token. The format for valid tokens is: %tokenFormat%.',
        self::TOKEN_DOES_NOT_EXIST  => 'Unknown token.',
        self::TOKEN_NO_LONGER_VALID => 'This token is no longer valid.',
        self::TOKEN_NOT_YET_VALID   => 'This token cannot be used (yet).',
    );

    /**
     * @var array
     */
    protected $_messageVariables = array(
        'reuse'       => '_reuse',
        'tokenFormat' => '_tokenFormat',
    );

    /**
     *
     * @var int
     */
    protected $_reuse;
    
    /**
     *
     * @var string
     */
    protected $_tokenFormat;

    /**
     *
     * @var Gems_Tracker
     */
    protected $tracker;
    
    public function __construct(Gems_Tracker $tracker, $tokenFormat, $reuse)
    {
        $this->_reuse       = $reuse;
        $this->_tokenFormat = $tokenFormat;
        $this->tracker      = $tracker;
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
     * @throws Zend_Valid_Exception If validation of $value is impossible
     */
    public function isValid($value)
    {
        // Make sure the value has the right format
        $value = $this->tracker->filterToken($value);

        if (strlen($value) !== strlen($this->_tokenFormat)) {
            $this->_error(self::NOT_TOKEN_FORMAT, $value);
            return false;
        }
        
        if ($token = $this->tracker->getToken($value)) {
            $currentDate = new MUtil_Date();
            
            if ($completionTime = $token->getCompletionTime()) {
            
                if ($this->_reuse >= 0) {
                    if ($completionTime->diffDays($currentDate) > $this->_reuse) {
                        // Oldest date AFTER completiondate. Oldest date is today minus reuse time
                        $this->_error(self::TOKEN_NO_LONGER_VALID, $value);
                        return false;
                    } else {
                        // It is completed and may be used to look up other
                        // valid tokens.
                        return true;
                    }
                } else {
                    $this->_error(self::TOKEN_NO_LONGER_VALID, $value);
                    return false;
                }
            }
            
            if ($fromDate = $token->getValidFrom()) {
                if ($currentDate->isEarlier($fromDate)) {
                    // Current date is BEFORE from date
                    $this->_error(self::TOKEN_NOT_YET_VALID, $value);
                    return false;
                }
            } else {
                $this->_error(self::TOKEN_NOT_YET_VALID, $value);
                return false;
            }
            
            if ($untilDate = $token->getValidUntil()) {
                if ($currentDate->isLater($untilDate)) {
                    //Current date is AFTER until date
                    $this->_error(self::TOKEN_NO_LONGER_VALID, $value);
                    return false;
                }
            }

            return true;
        } else {
            $this->_error(self::TOKEN_DOES_NOT_EXIST, $value);
            return false;
        }
    }
}
