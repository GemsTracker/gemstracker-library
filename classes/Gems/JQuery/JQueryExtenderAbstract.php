<?php

/**
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
            $this->setBasePath(\MUtil\Controller\Front::getRequest()->getBasePath());
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

