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
class MUtil_Validate_IsConfirmed extends Zend_Validate_Abstract
{
    /**
     * Error codes
     * @const string
     */
    const NOT_SAME           = 'notSame';
    const MISSING_FIELD_NAME = 'missingFieldName';

    protected $_messageTemplates = array(
        self::NOT_SAME           => "Must be the same as %fieldDescription%.",
        self::MISSING_FIELD_NAME => 'No field was provided to match against.',
    );

    /**
     * @var array
     */
    protected $_messageVariables = array(
        'fieldDescription' => '_fieldDescription'
    );

    /**
     * The field name against which to validate
     * @var string
     */
    protected $_fieldName;

    /**
     * Description of field name against which to validate
     * @var string
     */
    protected $_fieldDescription;

    /**
     * Sets validator options
     *
     * @param  string $fieldName  Field name against which to validate
     * $param string $fieldDescription  Description of field name against which to validate
     * @return void
     */
    public function __construct($fieldName = null, $fieldDescription = null)
    {
        if (null !== $fieldDescription) {
            $this->setFieldDescription($fieldDescription);
        }
        if (null !== $fieldName) {
            $this->setFieldName($fieldName);
        }
    }

    /**
     * Get field name against which to compare
     *
     * @return String
     */
    public function getFieldName()
    {
        return $this->_fieldname;
    }

    /**
     * Get field name against which to compare
     *
     * @return String
     */
    public function getFieldDescription()
    {
        return $this->_fieldDescription;
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
        $fieldName        = $this->getFieldName();

        if ($fieldName === null) {
            $this->_error(self::MISSING_FIELD_NAME);
            return false;
        }

        if ($value !== $context[$fieldName])  {
            $this->_error(self::NOT_SAME);
            return false;
        }

        return true;
    }

    /**
     * Set field name against which to compare
     *
     * @param  mixed $token
     * @return self
     */
    public function setFieldName($fieldName)
    {
        $this->_fieldname = $fieldName;

        if (! $this->_fieldDescription) {
            $this->setFieldDescription($fieldName);
        }

        return $this;
    }

    /**
     * Set field name against which to compare
     *
     * @param  mixed $description
     * @return self
     */
    public function setFieldDescription($description)
    {
        $this->_fieldDescription = $description;
        return $this;
    }
}
