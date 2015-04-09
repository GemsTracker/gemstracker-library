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
 * Class for A link element. Assumes first passed argument is the href attribute,
 * unless specified otherwise.
 *
 * @package    MUtil
 * @subpackage Html
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class MUtil_Html_AElement extends \MUtil_Html_HtmlElement
{
    /**
     * Most elements must be rendered even when empty, others should - according to the
     * xhtml specifications - only be rendered when the element contains some content.
     *
     * $renderWithoutContent controls this rendering. By default an element tag is output
     * but when false the tag will only be present if there is some content in it.
     *
     * Some examples of elements rendered without content are:
     *   a, br, hr, img
     *
     * Some examples of elements NOT rendered without content are:
     *   dd, dl, dt, label, li, ol, table, tbody, tfoot, thead and ul
     *
     * @see $_repeater
     *
     * @var boolean The element is rendered even without content when true.
     */
    public $renderWithoutContent = true;

    /**
     * An A element, shows the url as content when no other content is available.
     *
     * Any extra parameters are added as either content, attributes or handled
     * as special types, if defined as such for this element.
     *
     * @param mixed $href We assume the first element contains the href, unless a later element is explicitly specified as such
     * @param mixed $arg_array \MUtil_Ra::args arguments
     */
    public function __construct($href, $arg_array = null)
    {
        $args = \MUtil_Ra::args(func_get_args(), array('href' => 'MUtil_Html_HrefArrayAttribute'));

        if (isset($args['href']) && (! $args['href'] instanceof \MUtil_Html_AttributeInterface)) {
            $args['href'] = new \MUtil_Html_HrefArrayAttribute($args['href']);
        }

        parent::__construct('a', $args);

        $this->setOnEmpty($this->href);
    }

    /**
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
        if (isset($attribs['target']) && (! isset($attribs['onclick']))) {
            // Assumption that is not tested, but when clicking on a target link, no further bubble is needed.
            $attribs['onclick'] = "event.cancelBubble = true;";
        }
        return parent::_htmlAttribs($attribs);
    }

    /**
     * Static helper function to create an A element.
     *
     * Any extra parameters are added as either content, attributes or handled
     * as special types, if defined as such for this element.
     *
     * @param mixed $href We assume the first element contains the href, unless a later element is explicitly specified as such
     * @param mixed $arg_array \MUtil_Ra::args arguments
     */
    public static function a($href, $arg_array = null)
    {
        $args = \MUtil_Ra::args(func_get_args(), array('href' => 'MUtil_Html_HrefArrayAttribute'));
        return new self($args);
    }

    /**
     * Return a mailto: link object
     *
     * @param mixed $email
     * @param mixed $arg_array
     * @return \self
     */
    public static function email($email, $arg_array = null)
    {
        $args = \MUtil_Ra::args(func_get_args(), 1);
        if (isset($args['href'])) {
            $href = $args['href'];
            unset($args['href']);
        } else {
            if (! isset($args['title'])) {
                $args['title'] = $email;
            }
            $href = array('mailto:', $email);
        }
        if (! isset($args['onclick'])) {
            // Make sure the mail link only opens a mail window.
            $args['onclick'] = 'event.cancelBubble=true;';
        }

        return new self($href, $email, $args);
    }

    /**
     * Return a link object when $iff is true
     *
     * @param \MUtil_Lazy $iff The test
     * @param mixed $aArgs Arguments when the test is true
     * @param mixed $spanArgs Arguments when the test is false
     * @return mixed
     */
    public static function iflink($iff, $aArgs, $spanArgs = null)
    {
        if ($iff instanceof \MUtil_Lazy_LazyInterface) {
            if ($spanArgs) {
                return \MUtil_Lazy::iff($iff, \MUtil_Html::create('a', $aArgs), \MUtil_Html::create('span', $spanArgs, array('renderWithoutContent' => false)));
            } else {
                return \MUtil_Lazy::iff($iff, \MUtil_Html::create('a', $aArgs));
            }
        }
        if ($iff) {
            return \MUtil_Html::create('a', $aArgs);
        } elseif ($spanArgs) {
            return \MUtil_Html::create('span', $spanArgs, array('renderWithoutContent' => false));
        }
    }

    /**
     * Return a mailto link if $email exists and other wise return nothing.
     *
     * @param mixed $email
     * @param mixed $arg_array
     * @return mixed
     */
    public static function ifmail($email, $arg_array = null)
    {
        $args = func_get_args();
        if ($email instanceof \MUtil_Lazy_LazyInterface) {
            return \MUtil_Lazy::iff($email, call_user_func_array(array(__CLASS__, 'email'), $args));
        }
        if ($email) {
            return self::email($args);
        }
    }
}