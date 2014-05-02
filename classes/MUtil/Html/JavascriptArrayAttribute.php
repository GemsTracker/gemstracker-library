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
 * Default attribute for javascript attributes with extra functions for common tasks
 *
 * @package    MUtil
 * @subpackage Html
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version MUtil 1.2
 */
class MUtil_Html_JavascriptArrayAttribute extends MUtil_Html_ArrayAttribute
{
    /**
     * String used to glue items together
     *
     * Empty string as not each array element corresponds to a single command.
     *
     * @var string
     */
    protected $_separator = '';

    /**
     * Specially treated types for a specific subclass
     *
     * @var array function name => class
     */
    protected $_specialTypes = array(
        'addUrl' => 'MUtil_Html_UrlArrayAttribute',
    );

    /**
     *
     * @param string $type
     * @param mixed $arg_array MUtil_Ra::args
     */
    public function __construct($type, $arg_array = null)
    {
        $args = MUtil_Ra::args(func_get_args(), 1);
        parent::__construct($type, 'javascript:', $args);
    }

    /**
     * Add a cancel bubble command
     *
     * @param boolean $cancelBubble
     * @return MUtil_Html_JavascriptArrayAttribute (continuation pattern)
     */
    public function addCancelBubble($cancelBubble = true)
    {
        if ($cancelBubble) {
            $this->add("event.cancelBubble = true;");
        } else {
            $this->add("event.cancelBubble = false;");
        }
        return $this;
    }

    /**
     * Add a cancel bubble command
     *
     * @param boolean $cancelBubble
     * @return MUtil_Html_JavascriptArrayAttribute (continuation pattern)
     */
    public function addConfirm($question)
    {
        $this->add(array(
            "if (!confirm('",
            MUtil_Lazy::call('addslashes', $question),
            "')) {event.cancelBubble = true; return false;}"
            ));
        return $this;
    }

    /**
     * Add single code line
     *
     * @param mixed $line
     * @return MUtil_Html_JavascriptArrayAttribute (continuation pattern)
     */
    public function addLine($line_args)
    {
        $lines = MUtil_Ra::flatten(func_get_args());

        foreach ($lines as $line) {
            $this->add($line);
        }
        if (! (isset($line) && (';' == substr($line, -1)))) {
            $this->add(';');
        }

        return $this;
    }

    /**
     * Add a print command
     *
     * @return MUtil_Html_JavascriptArrayAttribute (continuation pattern)
     */
    public function addPrint()
    {
        $this->add('window.print();');
        return $this;
    }

    /**
     * Add a form submit
     *
     * @param string $condition Optional condition for submit
     * @return \MUtil_Html_JavascriptArrayAttribute
     */
    public function addSubmit($condition = null)
    {
        if ($condition) {
            $this->add("if ($condition) {this.form.submit();}");
        } else {
            $this->add('this.form.submit();');
        }

        return $this;
    }

    /**
     * Add a form submit when a value has changed
     *
     * @param string $condition Optional extra condition for submit
     * @return \MUtil_Html_JavascriptArrayAttribute
     */
    public function addSubmitOnChange($condition = null)
    {
        if ($condition) {
            $this->add("if (($condition) && (this.getAttribute('value') != this.value)) {this.form.submit();}");
        } else {
            $this->add("if (this.getAttribute('value') != this.value) {this.form.submit();}");
        }

        return $this;
    }

    /**
     * Add a url open command by specifying only the link
     *
     * @param mixed $href Anything, e.g. a MUtil_Html_UrlArrayAttribute that the code will transform to an url
     * @return MUtil_Html_JavascriptArrayAttribute (continuation pattern)
     */
    public function addUrl($href)
    {
        $last = is_array($this->_values) ? end($this->_values) : null;
        if (false === strpos($last, 'location.href')) {
            $this->_values[] = "location.href='";
            $this->_values[] = $href;
            $this->_values[] = "';";
        } else {
            $this->_values[] = $href;
        }

        return $this;
    }
}