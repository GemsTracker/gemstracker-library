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
 * 
 * @author Matijs de Jong
 * @since 1.0
 * @version 1.1
 * @package MUtil
 * @subpackage Html
 */

/**
 * The Sequence class is for sequentional Html content, kind of like a DOM document fragment.
 * 
 * It usual use is where you should return a single ElementInterface object but want to return a
 * sequence of objects. While implementing the MUtil_Html_ElementInterface it does have attributes 
 * nor does it return a tagname so it is not really an element, just treated as one.
 * 
 * This object also contains functions for processing parameters of special types. E.g. when a 
 * Zend_View object is passed it should be stored in $this->view, not added to the core array.
 * 
 * @author Matijs de Jong
 * @package MUtil
 * @subpackage Html
 */
class MUtil_Html_Sequence extends MUtil_ArrayString implements MUtil_Html_ElementInterface
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
     * @var Zend_View_Interface
     */
    public $view = null;

    public function __call($name, array $arguments)
    {
        $elem = MUtil_Html::createArray($name, $arguments);

        $this[] = $elem;

        return $elem;
    }

    public function __construct($arg_array = null)
    {
        parent::__construct();

        $args = MUtil_Ra::args(func_get_args());

        $this->init();

        // Passing the $args  to parent::__construct()
        // means offsetSet() is not called.
        foreach ($args as $key => $arg) {
            $this->offsetSet($key, $arg);
        }
    }

    public static function createSequence($args_array = null)
    {
        // BUG FIX: this function used to be called sequence() just
        // like all other static HtmlInterface element creation
        // functions, but as a sequence can contain a sequence
        // this lead to unexpected behaviour.

        $args = MUtil_Ra::args(func_get_args());

        $seq = new self($args);

        if (! isset($args['glue'])) {
            $seq->setGlue('');
        }

        return $seq;
    }

    public static function createSpaced($args_array = null)
    {
        // BUG FIX: this function used to be called spaced() just
        // like all other static HtmlInterface element creation
        // functions, but as a sequence can contain a sequence
        // this lead to unexpected behaviour.

        $args = MUtil_Ra::args(func_get_args());

        $seq = new self($args);

        if (! isset($args['glue'])) {
            $seq->setGlue(' ');
        }

        return $seq;
    }

    public function getTagName()
    {
        return null;
    }

    public function getView()
    {
        return $this->view;
    }

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
            MUtil_Echo::backtrace();
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
     * @param Zend_View_Abstract $view
     * @return string Correctly encoded and escaped html output
     */
    public function render(Zend_View_Abstract $view)
    {
        $html = '';
        $glue = $this->getGlue();

        if (null !== $view) {
            $this->setView($view);
        }
        // MUtil_Echo::r($this->count(), $glue);

        $view = $this->getView();

        $renderer = MUtil_Html::getRenderer();
        foreach ($this->getIterator() as $item) {
            $html .= $glue;
            $html .= $renderer->renderAny($view, $item);
        }

        return substr($html, strlen($glue));
    }

    /**
     * Set the View object
     *
     * @param  Zend_View_Interface $view
     * @return Zend_View_Helper_Abstract
     */
    public function setView(Zend_View_Interface $view)
    {
        $this->view = $view;
        return $this;
    }
}