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
 * @subpackage Html
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 *
 * @package    MUtil
 * @subpackage Html
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class MUtil_Html_Code_JavaScript extends MUtil_Html_Code_DynamicAbstract
{
    protected $_inHeader = true;

    /**
     * When true the output should be displayed in the result HEAD,
     * otherwise in the BODY.
     *
     * @return boolean
     */
    public function getInHeader()
    {
        if ($this->_inHeader instanceof MUtil_Lazy_LazyInterface) {
            return (boolean) MUtil_Lazy::raise($this->_inHeader);
        } else {
            return (boolean) $this->_inHeader;
        }
    }
    /**
     * Renders the element into a html string
     *
     * The $view is used to correctly encode and escape the output
     *
     * @param Zend_View_Abstract $view
     * @return string Correctly encoded and escaped html output
     */
    public function render(Zend_View_Abstract $view)
    {
        $content = $this->getContentOutput($view);

        // Of course this setting makes little difference if you have optimized
        // your JavaScript loading by putting all script tags at the end of
        // your body. (Except that inlineScript is always loaded last.)
        if ($this->getInHeader()) {
            $scriptTag = $view->headScript();
        } else {
            $scriptTag = $view->inlineScript();
        }
        $scriptTag->appendScript($content);

        return '';
    }

    /**
     * When true the result is displayed in the result HEAD,
     * otherwise in the BODY.
     *
     * @param boolean $value
     * @return MUtil_Html_Code_JavaScript (continuation pattern)
     */
    public function setInHeader($value = true)
    {
        $this->_inHeader = $value;
        return $this;
    }
}
