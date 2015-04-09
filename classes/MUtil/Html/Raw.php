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
 * The Raw class is used to output html without character encoding or escaping.
 *
 * Use this class when you have a string containg html or escaped texts that you
 * want to output without further processing.
 *
 * @package    MUtil
 * @subpackage Html
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.0
 */
class MUtil_Html_Raw implements \MUtil_Html_HtmlInterface
{
    /**
     * Whatever should be the output
     *
     * @var string
     */
    private $_value;

    /**
     * Create the class with the specified string content.
     *
     * @param string $value
     */
    public function __construct($value)
    {
        $this->setValue($value);
    }

    /**
     * Simple helper function
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getValue();
    }

    /**
     * Splits the content into an array where each items either contains
     *  - a tag (starts with '<' and ends with '>'
     *  - an entity (starts with '&' and ends with ';'
     *  - other string content (not starting with '<' or '&'
     *
     * This is a utility function that simplifies e.g. search and replace
     * without messing up the markup, e.g. in the Marker class
     *
     * @see \MUtil_Html_Marker
     *
     * @return array
     */
    public function getElements()
    {
        $CONTENT = 0; // In content between the tages
        $IN_APOS = 1; // In a single quoted string in an element tag
        $IN_ENT  = 2; // In an entity reference
        $IN_TAG  = 3; // In an element tag (closing or opening does not matter
        $IN_QUOT = 4; // In a double quoted string in an element tag

        $length   = strlen($this->_value);
        $result   = array();
        $startPos = 0;
        $mode     = $CONTENT;

        for ($i = 0; $i < $length; $i++) {
            switch ($this->_value[$i]) {
                case '&':
                    if ($CONTENT === $mode) {
                        if ($i > $startPos) {
                            // Add content (does not start with < or &
                            $result[] = substr($this->_value, $startPos, $i - $startPos);
                        }
                        $startPos = $i;
                        $mode     = $IN_ENT;
                    }
                    break;

                case ';':
                    if ($IN_ENT === $mode) {
                        // Add the entity (including & and ;
                        $result[] = substr($this->_value, $startPos, $i - $startPos + 1);
                        $startPos = $i + 1;
                        $mode     = $CONTENT;
                    }
                    break;

                case '<':
                    if (($CONTENT === $mode) || ($IN_ENT === $mode)) {
                        if ($i > $startPos) {
                            // Add content (does not start with < or &
                            $result[] = substr($this->_value, $startPos, $i - $startPos);
                        }
                        $startPos = $i;
                        $mode     = $IN_TAG;
                    }
                    break;

                case '>':
                    if ($IN_TAG === $mode) {
                        // Add the tag including opening '<' and closing '>'
                        $result[] = substr($this->_value, $startPos, $i - $startPos + 1);
                        $startPos = $i + 1;
                        $mode     = $CONTENT;
                    }
                    break;

                case '\'':
                    if ($IN_TAG === $mode) {
                        // Only a mode change when in an element tag
                        $mode = $IN_APOS;
                    } elseif ($IN_APOS === $mode) {
                        // End quote, resume tag mode
                        $mode = $IN_TAG;
                    }
                    break;

                case '"':
                    if ($IN_TAG === $mode) {
                        // End quote, resume tag mode
                        $mode = $IN_QUOT;
                    } elseif ($IN_QUOT === $mode) {
                        // End quote, resume tag mode
                        $mode = $IN_TAG;
                    }
                    break;

                // default:
                    // Intentional fall through
            }
        }

        if ($startPos < $length) {
            $result[] = substr($this->_value, $startPos);
        }
        return $result;
    }

    /**
     * The current content
     *
     * @return string
     */
    public function getValue()
    {
        return \MUtil_Lazy::raise($this->_value);
    }

    /**
     * Static helper function for creation, used by @see \MUtil_Html_Creator.
     *
     * @param string $value
     * @return \MUtil_Html_Raw
     */
    public static function raw($value)
    {
        $args = func_get_args();
        return new self($value);
    }

    /**
     * Echo the content.
     *
     * The $view is not used but required by the interface definition
     *
     * @param \Zend_View_Abstract $view
     * @return string Correctly encoded and escaped html output
     */
    public function render(\Zend_View_Abstract $view)
    {
        return $this->getValue();
    }

    /**
     * Change the content.
     *
     * @param string $value
     * @return \MUtil_Html_Raw
     */
    public function setValue($value)
    {
        $this->_value = $value;

        return $this;
    }
}
