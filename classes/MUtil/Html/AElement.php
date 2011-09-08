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

class MUtil_Html_AElement extends MUtil_Html_HtmlElement
{
    public $renderWithoutContent = true;

    public function __construct($href, $arg_array = null)
    {
        $args = MUtil_Ra::args(func_get_args(), array('href' => 'MUtil_Html_HrefArrayAttribute'));

        if (isset($args['href']) && (! $args['href'] instanceof MUtil_Html_AttributeInterface)) {
            $args['href'] = new MUtil_Html_HrefArrayAttribute($args['href']);
        }

        parent::__construct('a', $args);

        $this->setOnEmpty($this->href);
    }

    /**
     * Overrule the target attribute and provide the same functionality in a W3C compliant way
     *
     * If a realtarget attribute is specified this functionaluti is skipped (and the realtarget attribute is removed).
     *
     * If the target attribute is specified and no onclick attribute is specified the target is removed and
     * a compatible javascript onclick attribute is created.
     *
     * @param array $attribs From this array, each key-value pair is
     * converted to an attribute name and value.
     *
     * @return string The XHTML for the attributes.
     */
    protected function _htmlAttribs($attribs)
    {
        if (isset($attribs['realtarget'])) {
            unset($attribs['realtarget']);

        } elseif (isset($attribs['target']) && (! isset($attribs['onclick']))) {
            // It was so nice, but IE 9 really needs target
            /*
            $target = $attribs['target'];
            $attribs['onclick'] = "event.cancelBubble = true; window.open(this.href, '$target'); return false;";
            unset($attribs['target']); // */
            // Assumption that is not tested, but when clicking on a target link, no further bubble is needed.
            $attribs['onclick'] = "event.cancelBubble = true;";
        }
        return parent::_htmlAttribs($attribs);
    }

    public static function a($href, $arg_array = null)
    {
        $args = MUtil_Ra::args(func_get_args(), array('href' => 'MUtil_Html_HrefArrayAttribute'));
        return new self($args);
    }


    public static function email($email, $arg_array = null)
    {
        $args = MUtil_Ra::args(func_get_args(), 1);
        if (isset($args['href'])) {
            $href = $args['href'];
            unset($args['href']);
        } else {
            $href = array('mailto:', $email);
        }
        if (! isset($args['onclick'])) {
            // Make sure the mail link only opens a mail window.
            $args['onclick'] = 'event.cancelBubble=true;';
        }

        return new self($href, $email, $args);
    }

    public static function iflink($iff, $aArgs, $spanArgs = null)
    {
        if ($spanArgs) {
            return MUtil_Lazy::iff($iff, MUtil_Html::create('a', $aArgs), MUtil_Html::create('span', $spanArgs, array('renderWithoutContent' => false)));
        } else {
            return MUtil_Lazy::iff($iff, MUtil_Html::create('a', $aArgs));
        }
    }

    public static function ifmail($email, $arg_array = null)
    {
        $args = func_get_args();
        return MUtil_Lazy::iff($email, call_user_func_array(array(__CLASS__, 'email'), $args));
    }
}