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

abstract class MUtil_Html_AttributeAbstract implements MUtil_Html_AttributeInterface
{
    public $view;

    protected $_name;

    public function __construct($name, $value = null)
    {
        $this->_name = $name;

        if ($value) {
            $this->set($value);
        }
    }

    public function __toString()
    {
        // Output escaping is done in Zend_View_Helper_HtmlElement->_htmlAttribs()
        //
        // If the attribute needs the view to get the right data it must overrule __toString().
        return $this->get();
    }

    // public function add($value);
    // public function get();

    public function getAttributeName()
    {
        return $this->_name;
    }

    public function getView()
    {
        if (! $this->view) {
            require_once 'Zend/Controller/Action/HelperBroker.php';
            $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
            $this->setView($viewRenderer->view);
        }

        return $this->view;
    }

    public function render(Zend_View_Abstract $view)
    {
        $this->setView($view);

        // Output escaping is done in Zend_View_Helper_HtmlElement->_htmlAttribs()
        //
        // The reason for using render($view) is only in case the attribute needs the view to get the right data.
        // Those attributes must overrule render().
        return $this->get();
    }

    // public function set($value);

    public function setView(Zend_View_Abstract $view)
    {
        $this->view = $view;
    }
}