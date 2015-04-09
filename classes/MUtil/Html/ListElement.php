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
 * ListElement just inherits from HtmlElement but sets some
 * variables for automatic (x)html correct behaviour.
 *
 * @package    MUtil
 * @subpackage Html
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.0
 */
class MUtil_Html_ListElement extends \MUtil_Html_HtmlElement
{
    /**
     * 'li' is the only allowed child for all list elements.
     *
     * @var string|array A string or array of string values of the allowed element tags.
     */
    protected $_allowedChildTags = 'li';

    /**
     * Always end with a new line. Makes the html code better readable
     *
     * @var string Content added after the element.
     */
    protected $_appendString = "\n";

    /**
     * 'li' is still the only allowed element
     *
     * @var string The tagname of the element that should be created for content not having an $_allowedChildTags.
     */
    protected $_defaultChildTag = 'li';

    /**
     * Always start with a new line. Makes the html code better readable
     *
     * @var string Content added after the element.
     */
    protected $_prependString = "\n";

    /**
     * When empty a table element should not be output at rendering time as
     * a stand-alone <ol/> or <ul/> tag makes no sense.
     *
     * @see $_repeater
     *
     * @var boolean The element is rendered even without content when true.
     */
    public $renderWithoutContent = false;

    /**
     * Static helper function for creation, used by @see \MUtil_Html_Creator.
     *
     * @param mixed $arg_array Optional \MUtil_Ra::args processed settings
     * @return \MUtil_Html_ListElement (with dir tagName)
     */
    public static function dir($arg_array = null)
    {
        $args = func_get_args();
        return new self(__FUNCTION__, $args);
    }

    /**
     * Static helper function for creation, used by @see \MUtil_Html_Creator.
     *
     * @param mixed $arg_array Optional \MUtil_Ra::args processed settings
     * @return \MUtil_Html_ListElement (with menu tagName)
     */
    public static function menu($arg_array = null)
    {
        $args = func_get_args();
        return new self(__FUNCTION__, $args);
    }

    /**
     * Static helper function for creation, used by @see \MUtil_Html_Creator.
     *
     * @param mixed $arg_array Optional \MUtil_Ra::args processed settings
     * @return \MUtil_Html_ListElement (with ol tagName)
     */
    public static function ol($arg_array = null)
    {
        $args = func_get_args();
        return new self(__FUNCTION__, $args);
    }

    /**
     * Static helper function for creation, used by @see \MUtil_Html_Creator.
     *
     * @param mixed $arg_array Optional \MUtil_Ra::args processed settings
     * @return \MUtil_Html_ListElement (with ul tagName)
     */
    public static function ul($arg_array = null)
    {
        $args = func_get_args();
        return new self(__FUNCTION__, $args);
    }
}