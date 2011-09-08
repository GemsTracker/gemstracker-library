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
 * 
 * @author Matijs de Jong
 * @since 1.0
 * @version 1.1
 * @package MUtil
 * @subpackage Validate
 */

/**
 * 
 * @author Matijs de Jong
 * @package MUtil
 * @subpackage Validate
 */
class MUtil_Validate_ElevenTest extends Zend_Validate_Abstract
{
    const ORDER_LEFT_2_RIGHT = 1;
    const ORDER_RIGHT_2_LEFT = 2;

    /**
     * Error codes
     * @const string
     */
    const NOT_CHECK   = 'notCheck';
    const NOT_NUMBER  = 'notNumber';
    const TO_LONG     = 'toLong';
    const TO_SHORT    = 'toShort';

    protected $_messageTemplates = array(
        self::NOT_CHECK  => "This is not a valid %testDescription%.",
        self::NOT_NUMBER => "A %testDescription% cannot contain letters.",
        self::TO_LONG    => "%value% is too long for a %testDescription%. Should be %length% digits.",
        self::TO_SHORT   => "%value% is too short for a %testDescription%. Should be %length% digits.",
    );

    /**
     * @var array
     */
    protected $_messageVariables = array(
        'testDescription' => '_testDescription',
        'length' => '_length'
    );

    /**
     * The length of the number (when not 0).
     *  
     * Not used when $_numberOrder is an array
     *  
     * @var array|ORDER_LEFT_2_RIGHT|ORDER_RIGHT_2_LEFT
     */
    protected $_numberLength = 0;

    /**
     * The allowed lenght of the number, set by the isvalid function to be used in message templates
     */
    protected $_length = 0;

    /**
     * Decides the weight addressed to each number 
     *  
     * Set to array to specify weight value for each position.
     *  
     * @var array|ORDER_LEFT_2_RIGHT|ORDER_RIGHT_2_LEFT
     */
    protected $_numberOrder = self::ORDER_LEFT_2_RIGHT;

    /**
     * Description of the kind of test 
     *  
     * @var string
     */
    protected $_testDescription = 'input number';

    public function __construct($testDescription = null)
    {
        if ($testDescription) {
            $this->setTestDescription($testDescription);
        }
    }

    protected function _getCalculateWeights($valueLength)
    {
        $order = $this->getNumberOrder();

        if (is_array($order)) {
            return $order;
        }

        $length = $this->getNumberLength();
        if ($length < 1) {
            $length = $valueLength;
        }

        $newOrder = range(1, $length);

        if ($order == self::ORDER_RIGHT_2_LEFT) {
            return array_reverse($newOrder);
        } else {
            return $newOrder;
        }
    }

    public function getNumberLength()
    {
        return $this->_numberLength;
    }

    public function getNumberOrder()
    {
        return $this->_numberOrder;
    }

    /**
     * Get description of the kind of test 
     *
     * @return String
     */
    public function getTestDescription()
    {
        return $this->_testDescription;
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
        $this->_setValue((string) $value);

        // Remove non letter characters like . _ - \s
        $value = preg_replace('/[\W_]/', '', $value);

        if (preg_match('/[\D]/', $value)) {
            $this->_error(self::NOT_NUMBER);
            return false;
        }

        $weights = $this->_getCalculateWeights(strlen($value));
        $count   = count($weights);

        //Set the length for the message template
        $this->_length = $count;
        // MUtil_Echo::rs($value, $weights);

        if ($count != strlen($value)) {
            if ($count < strlen($value)) {
                $this->_error(self::TO_LONG);
                return false;
            } else {
                $this->_error(self::TO_SHORT);
                return false;
            }
        }

        $sum = 0;
        for ($i = 0; $i < $count; $i++) {
            $sum += ($value[$i] * $weights[$i]);
        }
        if ($sum % 11) {
            $this->_error(self::NOT_CHECK);
            return false;
        }
        
        return true;
    }

    public function setNumberLength($numberLength)
    {
        $this->_numberLength = $numberLength;
        return $this;
    }

    public function setNumberOrder($numberOrder)
    {
        $this->_numberOrder = $numberOrder;
        return $this;
    }

    /**
     * Set description of the kind of test 
     *  
     * @param string $description 
     * @return $this
     */
    public function setTestDescription($description)
    {
        $this->_testDescription = $description;
        return $this;
    }
}
