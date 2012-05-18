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
 * @author Michiel Rook <michiel@touchdownconsulting.nl>
 * @version $Id$
 * @package MUtil
 * @subpackage Validate
 */

/**
 * 
 * @author Michiel Rook <michiel@touchdownconsulting.nl>
 * @package MUtil
 * @subpackage Validate
 */
class MUtil_Validate_Pdf extends Zend_Validate_Abstract
{
    /**
     * Error constants
     */
    const ERROR_INVALID_VERSION = 'invalidVersion';

    /**
     * @var array Message templates
     */
    protected $_messageTemplates = array(
        self::ERROR_INVALID_VERSION => 'Unsupported PDF version %value% - only versions 1.0 - 1.4 are supported.'
    );

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
    public function isValid($value, $context = array())
    {
        $objFactory = Zend_Pdf_ElementFactory::createFactory(1);
        $parser = new Zend_Pdf_Parser($value, $objFactory, true);
        $version = $parser->getPDFVersion();
        
        if (version_compare($version, '1.4', '>')) {
            $this->_error(self::ERROR_INVALID_VERSION, $version);
            return false;
        }
        
        return true;
    }
}
