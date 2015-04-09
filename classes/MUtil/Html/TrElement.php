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
 * @package    MUtil
 * @subpackage Html
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 *
 * @package    MUtil
 * @subpackage Html
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class MUtil_Html_TrElement extends \MUtil_Html_HtmlElement implements \MUtil_Html_ColumnInterface
{
    /**
     * Most elements must be rendered even when empty, others should - according to the
     * xhtml specifications - only be rendered when the element contains some content.
     *
     * $renderWithoutContent controls this rendering. By default an element tag is output
     * but when false the tag will only be present if there is some content in it.
     *
     * @var boolean The element is rendered even without content when true.
     */
    public $renderWithoutContent = false;

    /**
     * In some elements only certain elements are allowed as content. By specifying
     * $_allowedChildTags the element automatically ensures this is the case.
     *
     * At construction the $_defaultChildTag of the object is added (when needed) to
     * the $_allowedChildTags.
     *
     * @var string|array A string or array of string values of the allowed element tags.
     */
    protected $_allowedChildTags = array('td', 'th');

    /**
     * Usually no text is appended after an element, but for certain elements we choose
     * to add a "\n" newline character instead, to keep the output readable in source
     * view.
     *
     * @var string Content added after the element.
     */
    protected $_appendString = "\n";

    /**
     * When repeating content using $_repeater you may want to output the content only when it has
     * changed.
     *
     * @see $_repeater
     *
     * @var boolean Do not output if the output is identical to the last time the element was rendered.
     */
    protected $_onlyWhenChanged = false;


    /**
     * @see $_onlyWhenChanged
     *
     * @var string Cache for last output for comparison
     */
    protected $_onlyWhenChangedValueStore = null;


    /**
     * Returns the cell or a \MUtil_MultiWrapper containing cells that occupy the column position, taking colspan and other functions into account.
     *
     * @param int $col The numeric column position, starting at 0;
     * @return \MUtil_Html_HtmlElement Probably an element of this type, but can also be something else, posing as an element.
     */
    public function getColumn($col)
    {
        $results = $this->getColumnArray($col);

        switch (count($results)) {
            case 0:
                return null;

            case 1:
                return reset($results);

            default:
                return new \MUtil_MultiWrapper($results);
        }
    }

    /**
     * Returns the cells that occupies the column position, taking colspan and other functions into account, in an array.
     *
     * @param int $col The numeric column position, starting at 0;
     * @return array Of probably one \MUtil_Html_HtmlElement
     */
    public function getColumnArray($col)
    {
        return array($this->getColumn($col));
    }

    /**
     * Return the number of columns, taking such niceties as colspan into account
     *
     * @return int
     */
    public function getColumnCount()
    {
        $count = 0;

        foreach ($this->_content as $cell) {
            $count += self::getCellWidth($cell);
        }

        return $count;
    }

    /**
     * Returns the cell's column width. A utility function.
     *
     * @param mixed $cell \MUtil_Html_ColumnInterface
     * @return int
     */
    public static function getCellWidth($cell)
    {
        if ($cell instanceof \MUtil_Html_ColumnInterface) {
            return $cell->getColumnCount();
        }

        if (isset($cell->colspan) && is_int($cell->colspan)) {
            return  intval($cell->colspan);
        }

        // Assume it is a single column
        return 1;
    }

    /**
     * When repeating content using $_repeater you may want to output the content only when it has
     * changed.
     *
     * @return boolean
     */
    public function getOnlyWhenChanged()
    {
        return $this->_onlyWhenChanged;
    }

    /**
     * Function to allow overloading  of tag rendering only
     *
     * Renders the element tag with it's content into a html string
     *
     * The $view is used to correctly encode and escape the output
     *
     * @param \Zend_View_Abstract $view
     * @return string Correctly encoded and escaped html output
     */
    protected function renderElement(\Zend_View_Abstract $view)
    {
        $result = parent::renderElement($view);

        if ($this->_onlyWhenChanged) {
            if ($result == $this->_onlyWhenChangedValueStore) {
                return null;
            }
            $this->_onlyWhenChangedValueStore = $result;
        }

        return $result;
    }
    
    /**
     * When repeating content using $_repeater you may want to output the content only when it has
     * changed.
     *
     * @see $_repeater
     *
     * @return \MUtil_Html_HtmlElement (continuation pattern)
     */
    public function setOnlyWhenChanged($value)
    {
        $this->_onlyWhenChanged = $value;
        return $this;
    }

    /**
     * Static helper function for creation, used by @see \MUtil_Html_Creator.
     *
     * @param mixed $arg_array Optional \MUtil_Ra::args processed settings
     * @return \MUtil_Html_TrElement
     */
    public static function tr($arg_array = null)
    {
        $args = func_get_args();
        return new self(__FUNCTION__, $args);
    }
}