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

/**
 * 
 * @author Matijs de Jong
 * @package MUtil
 * @subpackage Html
 */
class MUtil_Html_TBodyElement extends MUtil_Html_HtmlElement implements MUtil_Html_ColumnInterface
{
    public $defaultRowClass;

    public $renderWithoutContent = false;

    protected $_addtoLastChild = true;

    protected $_appendString = "\n";

    protected $_defaultChildTag = 'tr';

    protected $_defaultRowChildTag = 'td';

    protected $_onEmptyLocal = null;

    protected function _createDefaultTag($value, $offset = null)
    {
        $row = parent::_createDefaultTag($value, $offset = null);

        if ($this->defaultRowClass && (! isset($row->class))) {
            $row->class = $this->defaultRowClass;
        }

        $row->setDefaultChildTag($this->getDefaultRowChildTag());

        return $row;
    }

    /**
     * Returns the cell or a MUtil_MultiWrapper containing cells that occupy the column position, taking colspan and other functions into account.
     * 
     * @param int $col The numeric column position, starting at 0;
     * @return MUtil_Html_HtmlElement Probably an element of this type, but can also be something else, posing as an element.
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
                return new MUtil_MultiWrapper($results);
        }
    }
    
    /**
     * Returns the cells that occupies the column position, taking colspan and other functions into account, in an array.
     * 
     * @param int $col The numeric column position, starting at 0;
     * @return array Of probably one MUtil_Html_HtmlElement
     */
    public function getColumnArray($col)
    {
        $results = array();

        foreach ($this->_content as $row) {
            if ($row instanceof MUtil_Html_ColumnInterface) {
                $results = array_merge($results, $row->getColumnArray($col));
            }
        }

        return $results;
    }
    
    /**
     * Return the number of columns, taking such niceties as colspan into account
     * 
     * @return int
     */
    public function getColumnCount()
    {
        $counts[] = 0;

        foreach ($this->_content as $row) {
            if ($row instanceof MUtil_Html_ColumnInterface) {
                $counts[] = $row->getColumnCount();
            }
        }

        return max($counts);
    }

    public function getDefaultRowClass()
    {
        return $this->defaultRowClass;
    }

    public function getDefaultRowChildTag()
    {
        return $this->_defaultRowChildTag;
    }

    public function getOnEmpty($colcount = null)
    {
        if (! $this->_onEmptyLocal) {
            $this->setOnEmpty(null, $colcount);
        }

        return $this->_onEmptyLocal;
    }

    public function setDefaultRowClass($class)
    {
        $this->defaultRowClass = $class;
        return $this;
    }

    public function setDefaultRowChildTag($tag)
    {
        $this->_defaultRowChildTag = $tag;
        return $this;
    }

    public function setOnEmpty($content, $colcount = null)
    {
        if (($content instanceof MUtil_Html_ElementInterface) &&
            ($content->getTagName() ==  $this->_defaultChildTag)) {

            $this->_onEmptyContent = $content;

            if (isset($this->_onEmptyContent[0])) {
                $this->_onEmptyLocal = $this->_onEmptyContent[0];
            } else {
                $this->_onEmptyLocal = $this->_onEmptyContent->td();
            }

        } else {
            $this->_onEmptyContent = MUtil_Html::create($this->_defaultChildTag);
            $this->_onEmptyLocal   = $this->_onEmptyContent->td($content);

        }

        // Collcount tells us to span the empty content cell
        if ($colcount) {
            if ($colcount instanceof MUtil_Html_ColumnInterface) {
                // Lazy calculation of number of columns when this is a ColumnInterface
                $this->_onEmptyLocal->colspan = MUtil_Lazy::method($colcount, 'getColumnCount');

            } else {
                // Passed fixed number of columns, just set
                $this->_onEmptyLocal->colspan = $colcount;
            }
        } else {

            // Pass the row instead of the cell. Without a colspan
            // the programmer should add extra cells to it.
            $this->_onEmptyLocal = $this->_onEmptyContent;
        }

        return $this->_onEmptyLocal;
    }

    /**
     * Repeat the element when rendering. 
     * 
     * When repeatTags is false (the default) only the content is repeated but 
     * not the element tags. When repeatTags is true the both the tags and the
     * content are repeated.
     * 
     * @param mixed $repeater MUtil_Lazy_RepeatableInterface or something that can be made into one.
     * @param mixed $onEmptyContent Optional. When not null the content to display when the repeater does not result in data is set.
     * @param boolean $repeatTags Optional when not null the repeatTags switch is set.
     * @param mixed $colcount MUtil_Html_ColumnInterface or intefer. Span the onEmpty content over $colcount cells
     * @return MUtil_Html_TBodyElement (continuation pattern)
     */
    public function setRepeater($repeater, $onEmptyContent = null, $repeatTags = null, $colcount = null)
    {
        parent::setRepeater($repeater, null, $repeatTags);

        if ($onEmptyContent) {
            $this->setOnEmpty($onEmptyContent, $colcount);
        }

        return $this;
    }

    public function tr($arg_array = null)
    {
        $args = func_get_args();

        // Set default child tag first and het because otherwise
        // the children are created first and the default child tag
        // is set afterwards.
        if (! array_key_exists('DefaultChildTag', $args)) {
            array_unshift($args, array('DefaultChildTag' => $this->getDefaultRowChildTag()));
        }

        $tr = MUtil_Html::createArray('tr', $args);

        $this[] = $tr;

        if ((! isset($tr->class)) && ($class = $this->getDefaultRowClass())) {
            $tr->class = $class;
        }

        return $tr;
    }

    /**
     * Static helper function for creation, used by @see MUtil_Html_Creator.
     * 
     * @param mixed $arg_array Optional MUtil_Ra::args processed settings
     * @return MUtil_Html_TBodyElement with tag 'tbody'
     */
    public static function tbody($arg_array = null)
    {
        $args = func_get_args();
        return new self(__FUNCTION__, $args);
    }

    /**
     * Static helper function for creation, used by @see MUtil_Html_Creator.
     * 
     * @param mixed $arg_array Optional MUtil_Ra::args processed settings
     * @return MUtil_Html_TBodyElement with tag 'tfoot'
     */
    public static function tfoot($arg_array = null)
    {
        $args = func_get_args();
        return new self(__FUNCTION__, $args);
    }

    /**
     * Static helper function for creation, used by @see MUtil_Html_Creator.
     * 
     * @param mixed $arg_array Optional MUtil_Ra::args processed settings
     * @return MUtil_Html_TBodyElement with tag 'thead'
     */
    public static function thead($arg_array = null)
    {
        $args = func_get_args();
        return new self(__FUNCTION__, array('DefaultRowChildTag' => 'th'), $args);
    }
}