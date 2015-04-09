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
 * RepeatRenderer wraps itself around some content and returns at rendering
 * time that content repeated multiple times or the $_emptyContent when the
 * repeater is empty.
 *
 * Most of the functions are the just to implement the ElementInterface and
 * are nothing but a stub to the internal content. These functions will
 * throw errors if you try to use them in ways that the actual $_content does
 * not allow.
 *
 * @see \MUtil_Lazy_Repeatable
 *
 * @package    MUtil
 * @subpackage Html
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class MUtil_Html_RepeatRenderer implements \MUtil_Html_ElementInterface
{
    /**
     * The content to be repeated.
     *
     * @var mixed
     */
    protected $_content;

    /**
     * The content to show when the $_repeater returns no data.
     *
     * @var mixed Optional
     */
    protected $_emptyContent;

    /**
     * Any content to mixed between the instances of content.
     *
     * @var mixed Optional
     */
    protected $_glue;

    /**
     * The repeater containing a dataset
     *
     * @var \MUtil_Lazy_RepeatableInterface
     */
    protected $_repeater;

    /**
     *
     * @param \MUtil_Lazy_RepeatableInterface $repeater
     * @param string $glue Optional, content to display between repeated instances
     */
    public function __construct(\MUtil_Lazy_RepeatableInterface $repeater, $glue = null)
    {
        $this->setRepeater($repeater);
        $this->setGlue($glue);
    }

    public function append($value)
    {
        $this->_content[] = $value;

        return $value;
    }

    public function count()
    {
        return count($this->_content);
    }

    public function getContent()
    {
        return $this->_content;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->_content);
    }

    public function getOnEmpty()
    {
        return $this->_emptyContent;
    }

    public function getTagName()
    {
        if ($this->_content instanceof \MUtil_Html_ElementInterface) {
            return $this->_content->getTagName();
        }
        return null;
    }

    public function getRepeater()
    {
        return $this->_repeater;
    }

    public function hasRepeater()
    {
        return $this->_repeater ? true : false;
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->_content);
    }

    public function offsetGet($offset)
    {
        return $this->_content[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->_content[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->_content[$offset]);
    }

    /**
     * Renders the element into a html string
     *
     * The $view is used to correctly encode and escape the output
     *
     * @param \Zend_View_Abstract $view
     * @return string Correctly encoded and escaped html output
     */
    public function render(\Zend_View_Abstract $view)
    {
        $renderer = \MUtil_Html::getRenderer();
        if ($this->hasRepeater() && $this->_content) {
            $data = $this->getRepeater();
            if ($data->__start()) {
                $html = array();
                while ($data->__next()) {
                    $html[] = $renderer->renderAny($view, $this->_content);
                }

                if ($html) {
                    return implode($renderer->renderAny($view, $this->_glue), $html);
                }
            }
        }
        if ($this->_emptyContent) {
            return $renderer->renderAny($view, $this->_emptyContent);
        }

        return null;
    }

    public function setContent($content)
    {
        $this->_content = $content;
        return $this;
    }

    private function setRepeater(\MUtil_Lazy_RepeatableInterface $data)
    {
        $this->_repeater = $data;
        return $this;
    }

    public function setGlue($glue)
    {
        $this->_glue = $glue;
        return $this;
    }

    public function setOnEmpty($content)
    {
        $this->_emptyContent = $content;
        return $this;
    }
}