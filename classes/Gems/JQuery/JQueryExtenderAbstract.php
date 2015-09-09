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
 * @package    Gems
 * @subpackage JQuery
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * @package    Gems
 * @subpackage JQuery
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
abstract class Gems_JQuery_JQueryExtenderAbstract implements \MUtil_Html_HtmlInterface
{
    protected $basepath;
    protected $jqueryParams;
    protected $localScriptFiles;
    protected $name;
    protected $view;

    public function __construct(array $options = null)
    {
        $args = \MUtil_Ra::args(func_get_args());

        foreach ($args as $name => $arg) {
            if (! is_int($name)) {
                if (method_exists($this, $fname = 'set' . ucfirst($name))) {
                    $this->$fname($arg);
                } else {
                    $this->setJQueryParam($name, $arg);
                }
            }
        }
    }

    public function getBasePath()
    {
        if (null === $this->basepath) {
            $front = \Zend_Controller_Front::getInstance();
            $this->setBasePath($front->getRequest()->getBasePath());
        }

        return $this->basepath;
    }
    public function getJQueryHandler()
    {
        return \ZendX_JQuery_View_Helper_JQuery::getJQueryHandler();
    }

    public function getJQueryParam($name)
    {
        if (isset($this->jqueryParams[$name])) {
            return $this->jqueryParams[$name];
        }
    }

    public function getJsonParameters()
    {
        return \ZendX_JQuery::encodeJson($this->jqueryParams);
    }

    public function getName()
    {
        if (null === $this->name) {
            $name = strrchr(get_class($this), '_');
            if (! $name) {
                $name = '_' . get_class($this);
            }

            $this->name = strtolower($name[1]) . substr($name, 2);
        }

        return $this->name;
    }

    abstract public function getSelector();

    public function getView()
    {
        return $this->view;
    }

    public function render(\Zend_View_Abstract $view)
    {
        $this->setView($view);

        $this->setOnLoad($this->getSelector());

        return '';
    }

    public function setBasePath($basepath)
    {
        $this->basepath = $basepath;
        return $this;
    }

    public function setJQueryParam($name, $arg)
    {
        $this->jqueryParams[$name] = $arg;
        return $this;
    }

    public function setOnLoad($selector)
    {
        $jquery = $this->getView()->jQuery();
        if ($this->localScriptFiles) {
            $basepath = $this->getBasePath();
            foreach ((array) $this->localScriptFiles as $file) {
                $jquery->addJavascriptFile($basepath . $file);
            }
        }

        $js = sprintf('%s("%s").%s(%s);',
            $this->getJQueryHandler(),
            $selector,
            $this->getName(),
            $this->getJsonParameters()
        );

        $jquery->addOnLoad($js);
    }

    public function setView(\Zend_View_Abstract $view)
    {
        if (isset($view->request) && ($view->request instanceof \Zend_Controller_Request_Http)) {
            $this->setBasePath($view->request->getBasePath());
        }

        if (! \MUtil_JQuery::usesJQuery($view)) {
            \ZendX_JQuery::enableView($view);
        }

        $this->view = $view;
    }
}

