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
 * @subpackage Form_Element
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * @package    MUtil
 * @subpackage Form_Element
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */
class MUtil_Form_Element_Html extends Zend_Form_Element_Xhtml implements MUtil_Form_Element_NoFocusInterface
{
    public $helper = 'html';

    /**
     * Overloading: allow rendering specific decorators
     *
     * Call renderDecoratorName() to render a specific decorator.
     *
     * @param  string $method
     * @param  array $args
     * @return MUtil_Html_HtmlElement or at least something that implements the MUtil_Html_HtmlInterface interface
     * @throws Zend_Form_Exception for invalid decorator or invalid method call
     */
    public function __call($method, $args)
    {
        if ('render' == substr($method, 0, 6)) {
            return parent::__call($method, $args);
        }

        $elem = MUtil_Html::createArray($method, $args);

        $value = $this->getValue();

        if (! $value instanceof MUtil_Html_ElementInterface) {
            $value = new MUtil_Html_Sequence();
        }
        $value->append($elem);
        $this->setValue($value);

        return $elem;
    }

    /**
     * The HTML element is always valid and we don't want the value to be changed by this function
     */
    public function isValid($value, $context = null) {
        return true;
    }
}
