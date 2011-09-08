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
 * @author Matijs de Jong
 * @since 1.0
 * @version 1.1
 * @package MUtil
 * @subpackage Html
 */

class MUtil_Html_ElementDecorator extends Zend_Form_Decorator_Abstract
{
    protected $_html_element;
    protected $_prologue;

    public function getHtmlElement()
    {
        return $this->_html_element;
    }

    public function getPrologue()
    {
        return $this->_prologue;
    }

    /**
     * Render the element
     *
     * @param  string $content Content to decorate
     * @return string
     */
    public function render($content)
    {
        if ((null === ($element = $this->getElement())) ||
            (null === ($view = $element->getView())) ||
            (null === ($htmlelement = $this->getHtmlElement()))) {
            return $content;
        }

        if ($prologue = $this->getPrologue()) {
            if ($prologue instanceof MUtil_Lazy_RepeatableFormElements) {
                // Not every browser can handle empty divs (e.g. IE 6)
                if ($hidden = $prologue->getHidden()) {
                    $prologue = MUtil_Html::create()->div($hidden);
                } else {
                    $prologue = null;
                }
            }
            if ($prologue instanceof MUtil_Html_HtmlInterface) {
                $prologue = $prologue->render($view);
            } else {
                $prologue = MUtil_Html::renderAny($view, $prologue);
            }
        } else {
            $prologue = '';
        }

        $result = $this->renderElement($htmlelement, $view);

        if (parent::APPEND == $this->getPlacement()) {
            return $prologue . $result . $content;
        } else {
            return $content . $prologue . $result;
        }
    }


    /**
     * Render the html element
     *
     * Override this method rather than render() as this
     * is saver and the default logic is handled.
     *
     * @param  string $content Content to decorate
     * @return string
     */
    public function renderElement(MUtil_Html_HtmlInterface $htmlElement, Zend_View $view)
    {
        return $htmlElement->render($view);
    }

    public function setHtmlElement(MUtil_Html_HtmlInterface $htmlElement)
    {
        $this->_html_element = $htmlElement;
        return $this;
    }

    public function setPrologue($prologue)
    {
        $this->_prologue = $prologue;
        return $this;
    }
}

