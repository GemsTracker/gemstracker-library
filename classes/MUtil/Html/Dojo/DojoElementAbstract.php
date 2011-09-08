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
 * @subpackage Html_Dojo
 */

abstract class MUtil_Html_Dojo_DojoElementAbstract extends MUtil_Html_HtmlElement
{
    /**
     * Dijit being used
     * @var string
     */
    public $dijit;

    /**
     * MUtil_Html_Dojo_DataCollector
     * @var MUtil_Html_Dojo_DataCollector
     */
    public $dojo;

    /**
     * Dojo module to use
     * @var string
     */
    public $module;


    public function __construct($tagName, $arg_array = null)
    {
        $args = MUtil_Ra::args(func_get_args(), 1);

        parent::__construct($tagName, $args);

        $this->dojo = new MUtil_Html_Dojo_DojoData();
    }

    /**
     * Whether or not to use declarative dijit creation
     *
     * @return bool
     */
    protected function _useDeclarative()
    {
        return Zend_Dojo_View_Helper_Dojo::useDeclarative();
    }

    /**
     * Whether or not to use programmatic dijit creation
     *
     * @return bool
     */
    protected function _useProgrammatic()
    {
        return Zend_Dojo_View_Helper_Dojo::useProgrammatic();
    }

    /**
     * Whether or not to use programmatic dijit creation w/o script creation
     *
     * @return bool
     */
    protected function _useProgrammaticNoScript()
    {
        return Zend_Dojo_View_Helper_Dojo::useProgrammaticNoScript();
    }

    public function render(Zend_View_Abstract $view)
    {
        $this->dojo->processView($view, $this);

        return parent::render($view);
    }
}