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
 * Class to mark text in HTML content, e.g. to nmark the result of a search statement
 *
 * @package    MUtil
 * @subpackage Html
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class MUtil_Html_Marker
{
    /**
     * Marker for tags in
     */
    const TAG_MARKER = "\0";

    /**
     *
     * @var string Any other attributes to add to the tag
     */
    private $_attributes = null;

    /**
     *
     * @var string The encoding used
     */
    private $_encoding = 'UTF-8';

    /**
     *
     * @var array Calculated array containing the texts to replace
     */
    private $_replaces;

    /**
     *
     * @var array Calculated array containing the texts to search for
     */
    private $_searches;

    /**
     *
     * @var string The element name for the tagged parts
     */
    private $_tag;

    /**
     * Create the marker
     *
     * @param array $searches The texts parts to mark
     * @param string $tag The element name for the tagged parts
     * @param string $encoding The encoding used
     * @param string $attributes Any other attributes to add to the tag
     */
    public function __construct($searches, $tag, $encoding, $attributes = 'class="marked"')
    {
        $this->setEncoding($encoding);

        $this->_tag      = $tag;
        $this->_searches = (array) $searches;

        if ($attributes) {
            $this->_attributes = ' ' . trim($attributes) . ' ';
        }

    }

    /**
     * Replace the tag markers with the actual tag
     *
     * @param string $text
     * @return string
     */
    private function _fillTags($text)
    {
        return str_replace(
                array('<' . self::TAG_MARKER, '</' . self::TAG_MARKER),
                array('<' . $this->_tag . $this->_attributes, '</' . $this->_tag),
                $text);
    }

    /**
     * Find and replace the actual texts
     *
     * @param string $text
     * @return string
     */
    private function _findTags($text)
    {
        return str_ireplace($this->_searches, $this->_replaces, $text);
    }

    /**
     * Generic html output escape function
     *
     * @param string $value
     * @return string
     */
    public function escape($value)
    {
        return htmlspecialchars($value, ENT_COMPAT, $this->_encoding);
    }

    /**
     * Mark the searches in $value
     *
     * @param mixed $value Lazy, Html, Raw or string
     * @return \MUtil_Html_Raw
     */
    public function mark($value)
    {
        if (! $this->_replaces) {
            // Late setting of search & replaces
            $searches = $this->_searches;
            $this->_searches = array();

            // Do not use the $tag itself here: str_replace will then replace
            // the text of tag itself on later finds
            $topen  = '<' . self::TAG_MARKER . '>';
            $tclose = '</' . self::TAG_MARKER . '>';

            foreach ((array) $searches as $search) {
                $searchHtml = $this->escape($search);
                $this->_searches[] = $searchHtml;
                $this->_replaces[] = $topen . $searchHtml . $tclose;
            }
        }

        if ($value instanceof \MUtil_Lazy_LazyInterface) {
            $value = \MUtil_Lazy::rise($value);
        }

        if ($value instanceof \MUtil_Html_Raw) {
            $values = array();
            // Split into HTML Elements
            foreach ($value->getElements() as $element) {
                if (strlen($element)) {
                    switch ($element[0]) {
                        case '<':
                        case '&':
                            // No replace in element
                            $values[] = $element;
                            break;

                        default:
                            $values[] = $this->_findTags($element);
                    }
                }
            }
            // \MUtil_Echo::r($values);

            return $value->setValue($this->_fillTags(implode('', $values)));

        } elseif ($value instanceof \MUtil_Html_HtmlElement) {
            foreach ($value as $key => $item) {
                // \MUtil_Echo::r($key);
                $value[$key] = $this->mark($item);
            }
            return $value;

        } elseif ($value || ($value === 0)) {
            // \MUtil_Echo::r($value);
            $valueHtml = $this->escape($value);

            $valueTemp = $this->_findTags($valueHtml);

            return new \MUtil_Html_Raw($this->_fillTags($valueTemp));
        }
    }

    /**
     * Function to allow later setting of encoding.
     *
     * @see htmlspecialchars()
     *
     * @param string $encoding Encoding htmlspecialchars
     * @return \MUtil_Html_Marker (continuation pattern)
     */
    public function setEncoding($encoding)
    {
        if ($encoding) {
            $this->_encoding = $encoding;
        }

        return $this;
    }

    /**
     * Function to allow later setting of tag name.
     *
     * @param string $tagName Html element tag name
     * @return \MUtil_Html_Marker (continuation pattern)
     */
    public function setTagName($tagName)
    {
        $this->_tag = $tagName;

        return $this;
    }
}

