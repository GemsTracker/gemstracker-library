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
 * Sprintf class is used to use sprintf with renderable content .
 *
 * @package    MUtil
 * @subpackage Html
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.2
 */
class MUtil_Html_Sprintf extends \ArrayObject implements \MUtil_Html_ElementInterface
{
    /**
     * Object classes that should not be added to the core array, but should be set using
     * a setXxx() function.
     *
     * This parameter enables sub-classes to define their own special types.
     *
     * @var array Null or array containing className => setFunction()
     */
    protected $_specialTypes;

    /**
     * The default special types that are always valid for children of this class.
     *
     * @var array
     */
    private $_specialTypesDefault = array(
        'Zend_View' => 'setView',
        );

    /**
     * View object
     *
     * @var \Zend_View_Interface
     */
    public $view = null;

    /**
     * Adds an HtmlElement to this element
     *
     * @see \MUtil_Html_Creator
     *
     * @param string $name Function name becomes tagname (unless specified otherwise in \MUtil_Html_Creator)
     * @param array $arguments The content and attributes values
     * @return \MUtil_Html_HtmlElement With '$name' tagName
     */
    public function __call($name, array $arguments)
    {
        $elem = \MUtil_Html::createArray($name, $arguments);

        $this[] = $elem;

        return $elem;
    }

    /**
     *
     * @param mixed $arg_array \MUtil_Ra::args parameter passing
     */
    public function __construct($arg_array = null)
    {
        parent::__construct();

        $args = \MUtil_Ra::args(func_get_args());

        $this->init();

        // Passing the $args  to parent::__construct()
        // means offsetSet() is not called.
        foreach ($args as $key => $arg) {
            $this->offsetSet($key, $arg);
        }
    }

    /**
     * Interface required function, not in real use
     *
     * @return null
     */
    public function getTagName()
    {
        return null;
    }

    /**
     * Get the current view
     *
     * @return \Zend_View
     */
    public function getView()
    {
        return $this->view;
    }

    /**
     * Initiator functions - to prevent constructor overloading
     */
    protected function init()
    {
        if ($this->_specialTypes) {
            $this->_specialTypes = $this->_specialTypes + $this->_specialTypesDefault;
        } else {
            $this->_specialTypes = $this->_specialTypesDefault;
        }
    }

    public function offsetSet($index, $newval)
    {
        if ($index && (! is_numeric($index))) {
            if (method_exists($this, $fname = 'set' . $index)) {
                $this->$fname($newval);

                return;
            }
        }

        /*
        if (! $this->_specialTypes) {
            \MUtil_Echo::backtrace();
        } // */
        foreach ($this->_specialTypes as $class => $method) {
            if ($newval instanceof $class) {
                $this->$method($newval, $index);

                return;
            }
        }

        return parent::offsetSet($index, $newval);
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
        if (null === $view) {
            $view = $this->getView();
        } else {
            $this->setView($view);
        }

        $params = \MUtil_Html::getRenderer()->renderArray($view, $this->getIterator(), false);

        if ($params) {
            return call_user_func_array('sprintf', $params);
        }

        return '';
    }

    /**
     * Set the View object
     *
     * @param  \Zend_View_Interface $view
     * @return \Zend_View_Helper_Abstract
     */
    public function setView(\Zend_View_Interface $view)
    {
        $this->view = $view;
        return $this;
    }

    /**
     *
     * @param mixed $arg_array \MUtil_Ra::args parameter passing
     */
    public static function sprintf($arg_array = null)
    {
        $args = \MUtil_Ra::args(func_get_args());

        return new self($args);
    }
}
