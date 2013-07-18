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
 * @subpackage Html
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Standard interface for attributes in this package.
 *
 * The interface ensure the ability to not only get and set the
 * value, but also the attribute name and the ability to add to
 * the content in a manner as defined by the attribute itself.
 *
 * E.g. adding to a class attribute usually involves seperating
 * the new addition with a space.
 *
 * @package    MUtil
 * @subpackage Html
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
interface MUtil_Html_AttributeInterface extends MUtil_Html_HtmlInterface
{
    /**
     * Returns an unescape string version of the attribute
     *
     * Output escaping is done elsewhere, e.g. in Zend_View_Helper_HtmlElement->_htmlAttribs()
     *
     * @return string
     */
    public function __toString();

    /**
     * Add to the attribute
     *
     * @param mixed $value
     * @return \MUtil_Html_AttributeInterface (continuation pattern)
     */
    public function add($value);

    /**
     * Get the scalar value of this attribute.
     *
     * @return string | int | null
     */
    public function get();

    /**
     * Returns the attribute name
     *
     * @return string
     */
    public function getAttributeName();

    // inherited: public function render(Zend_View_Abstract $view);

    /**
     * Set the value of this attribute.
     *
     * @return \MUtil_Html_AttributeInterface (continuation pattern)
     */
    public function set($value);
}