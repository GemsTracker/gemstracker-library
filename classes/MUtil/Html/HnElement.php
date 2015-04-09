<?php

/**
 * Copyright (c) 2012, Erasmus MC
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
 * @since      Class available since version 1.5.5
 */
class MUtil_Html_HnElement extends \MUtil_Html_HtmlElement
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
     * Static helper function for creation, used by @see \MUtil_Html_Creator.
     *
     * @param mixed $arg_array Optional \MUtil_Ra::args processed settings
     * @return \MUtil_Html_TrElement
     */
    public static function h1($arg_array = null)
    {
        $args = func_get_args();
        return new self(__FUNCTION__, $args);
    }

    /**
     * Static helper function for creation, used by @see \MUtil_Html_Creator.
     *
     * @param mixed $arg_array Optional \MUtil_Ra::args processed settings
     * @return \MUtil_Html_TrElement
     */
    public static function h2($arg_array = null)
    {
        $args = func_get_args();
        return new self(__FUNCTION__, $args);
    }

    /**
     * Static helper function for creation, used by @see \MUtil_Html_Creator.
     *
     * @param mixed $arg_array Optional \MUtil_Ra::args processed settings
     * @return \MUtil_Html_TrElement
     */
    public static function h3($arg_array = null)
    {
        $args = func_get_args();
        return new self(__FUNCTION__, $args);
    }

    /**
     * Static helper function for creation, used by @see \MUtil_Html_Creator.
     *
     * @param mixed $arg_array Optional \MUtil_Ra::args processed settings
     * @return \MUtil_Html_TrElement
     */
    public static function h4($arg_array = null)
    {
        $args = func_get_args();
        return new self(__FUNCTION__, $args);
    }

    /**
     * Static helper function for creation, used by @see \MUtil_Html_Creator.
     *
     * @param mixed $arg_array Optional \MUtil_Ra::args processed settings
     * @return \MUtil_Html_TrElement
     */
    public static function h5($arg_array = null)
    {
        $args = func_get_args();
        return new self(__FUNCTION__, $args);
    }

    /**
     * Static helper function for creation, used by @see \MUtil_Html_Creator.
     *
     * @param mixed $arg_array Optional \MUtil_Ra::args processed settings
     * @return \MUtil_Html_TrElement
     */
    public static function h6($arg_array = null)
    {
        $args = func_get_args();
        return new self(__FUNCTION__, $args);
    }
}
