<?php

/**
 * Copyright (c) 2012, Erasmus MC
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
 * @package    Gems
 * @subpackage Validate
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Check for one of the two values being filled
 *
 * @package    Gems
 * @subpackage Validate
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6
 */
class Gems_Validate_OneOf extends \Zend_Validate_Abstract
{
    /**
     * Error codes
     * @const string
     */
    const NEITHER  = 'neither';

    protected $_messageTemplates = array(
        self::NEITHER => "Either '%description%' or '%fieldDescription%' must be entered.",
    );

    /**
     * @var array
     */
    protected $_messageVariables = array(
        'description' => '_description',
        'fieldDescription' => '_fieldDescription'
    );


    protected $_description;

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
    public function __construct($description, $fieldName, $fieldDescription)
    {
        $this->_description = $description;
        $this->_fieldName = $fieldName;
        $this->_fieldDescription = $fieldDescription;
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

        $fieldSet = (boolean) isset($context[$this->_fieldName]) && $context[$this->_fieldName];
        $valueSet = (boolean) $value;

        if ($valueSet && (! $fieldSet))  {
            return true;
        }

        if ((! $valueSet) && $fieldSet)  {
            return true;
        }

        $this->_error(self::NEITHER);
        return false;
    }
}
