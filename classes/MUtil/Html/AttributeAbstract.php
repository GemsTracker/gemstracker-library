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
 * Basic class for all attributes, does the rendering and attribute name parts,
 * but no value processing.
 *
 * @package    MUtil
 * @subpackage Html
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
abstract class MUtil_Html_AttributeAbstract implements \MUtil_Html_AttributeInterface
{
    /**
     *
     * @var type
     */
    public $name;

    /**
     *
     * @var \Zend_Controller_Request_Abstract
     */
    public $request;

    /**
     *
     * @var \Zend_View_Abstract
     */
    public $view;

    /**
     *
     * @param string $name The name of the attribute
     * @param mixed $value
     */
    public function __construct($name, $value = null)
    {
        $this->name = $name;

        if ($value) {
            $this->set($value);
        }
    }

    /**
     * Returns an unescape string version of the attribute
     *
     * Output escaping is done elsewhere, e.g. in \Zend_View_Helper_HtmlElement->_htmlAttribs()
     *
     * If a subclass needs the view for the right output and the view might not be set
     * it must overrule __toString().
     *
     * @return string
     */
    public function __toString()
    {
        return $this->get();
    }

    // public function add($value);
    // public function get();

    /**
     * Returns the attribute name
     *
     * @return string
     */
    public function getAttributeName()
    {
        return $this->name;
    }

    /**
     *
     * @return \Zend_Controller_Request_Abstract
     */
    public function getRequest()
    {
        if (! $this->request) {
            $front = \Zend_Controller_Front::getInstance();
            $this->request = $front->getRequest();
        }

        return $this->request;
    }

    /**
     *
     * @return \Zend_View_Abstract
     */
    public function getView()
    {
        if (! $this->view) {
            require_once 'Zend/Controller/Action/HelperBroker.php';
            $viewRenderer = \Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
            $this->setView($viewRenderer->view);
        }

        return $this->view;
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
        $this->setView($view);

        // Output escaping is done in \Zend_View_Helper_HtmlElement->_htmlAttribs()
        //
        // The reason for using render($view) is only in case the attribute needs the view to get the right data.
        // Those attributes must overrule render().
        return $this->get();
    }

    // public function set($value);

    /**
     *
     * @param \Zend_Controller_Request_Abstract $request
     * @return \MUtil_Html_AttributeAbstract  (continuation pattern)
     */
    public function setRequest(\Zend_Controller_Request_Abstract $request)
    {
        $this->request = $request;
        return $this;
    }

    /**
     *
     * @param \Zend_View_Abstract $view
     */
    public function setView(\Zend_View_Abstract $view)
    {
        $this->view = $view;
    }
}