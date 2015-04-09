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
 * @version    $Id: UniqueValue.php 2436 2015-02-20 11:47:27Z matijsdejong $
 */

/**
 *
 *
 * @package    MUtil
 * @subpackage Validate
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.4 23-jan-2014 17:57:39
 */
class MUtil_Validate_Model_UniqueValue extends \Zend_Validate_Abstract
{
    /**
     * Error constants
     */
    const ERROR_RECORD_FOUND    = 'recordFound';

    /**
     * @var array Message templates
     */
    protected $_messageTemplates = array(
        self::ERROR_RECORD_FOUND    => 'A duplicate record matching \'%value%\' was found.',
    );

    /**
     *
     * @var array
     */
    protected $_fields;

    /**
     *
     * @var \MUtil_Model_ModelAbstract
     */
    protected $_model;

    /**
     *
     * @param \MUtil_Model_ModelAbstract $model
     * @param string|array $field A field to check or an array of fields to check for an
     * unique value combination, though only the value of the first will be shown
     */
    public function __construct(\MUtil_Model_ModelAbstract $model, $field)
    {
        $this->_model  = $model;
        $this->_fields = (array) $field;
    }

    /**
     * Returns true if and only if $value meets the validation requirements
     *
     * If $value fails validation, then this method returns false, and
     * getMessages() will return an array of messages that explain why the
     * validation failed.
     *
     * @param  mixed $value
     * @param  array $context
     * @return boolean
     * @throws \Zend_Validate_Exception If validation of $value is impossible
     */
    public function isValid($value, $context = array())
    {
        $this->_setValue($value);

        // Make sure the (optionally filtered) value is in the context
        $context[reset($this->_fields)] = $value;

        $filter = array();
        foreach ($this->_fields as $name) {
            // Return valid when not all the fields to check for are in the context
            if (! isset($context[$name])) {
                return true;
            }

            $filter[$name] = $context[$name];
        }

        $check = array();
        $doGet = $this->_model->hasItemsUsed();
        $keys  = $this->_model->getKeys();
        foreach ($keys as $id => $key) {
            if ($doGet) {
                // Make sure the item is used
                $this->_model->get($key);
            }
            if (isset($context[$id])) {
                $check[$key] = $context[$id];
            } elseif (isset($context[$key])) {
                $check[$key] = $context[$key];
            } else {
                // Not all keys are in => therefore this is a new item
                $check = false;
                break;
            }
        }

        $rows = $this->_model->load($filter);

        if (! $rows) {
            return true;
        }

        if (! $check) {
            // Rows where found while it is a new item
            $this->_error(self::ERROR_RECORD_FOUND);
            return false;
        }

        $count = count($check);
        foreach ($rows as $row) {
            // Check for return of the whole check
            if (count(array_intersect_assoc($check, $row)) !== $count) {
                // There exists a row with the same values but not the same keys
                $this->_error(self::ERROR_RECORD_FOUND);
                return false;
            }
        }

        return true;
    }
}
