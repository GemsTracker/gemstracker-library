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
 * @subpackage Form
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Base form class with extensions for correct load paths, autosubmit forms and registry use.
 *
 * @package    Gems
 * @subpackage Form
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Form extends MUtil_Form implements MUtil_Registry_TargetInterface
{
    /**
     * If set this holds the url and targetid for the autosubmit
     *
     * @var array
     */
    protected $_autosubmit = null;

    /**
     * This variable holds all the stylesheets attached to this form
     *
     * @var array
     */
    protected $_css = array();

    /**
     * This variable holds all the scripts attached to this form
     *
     * @var array
     */
	protected $_scripts = null;

    /**
     * Constructor
     *
     * Registers form view helper as decorator
     *
     * @param string $name
     * @param mixed $options
     * @return void
     */
    public function __construct($options = null)
    {
        parent::__construct($options);

        $this->addPrefixPath(GEMS_PROJECT_NAME_UC . '_Form_Decorator', GEMS_PROJECT_NAME_UC . '/Form/Decorator/', Zend_Form::DECORATOR);
        $this->addPrefixPath(GEMS_PROJECT_NAME_UC . '_Form_Element',   GEMS_PROJECT_NAME_UC . '/Form/Element/',   Zend_Form::ELEMENT);
        $this->addPrefixPath('Gems_Form_Decorator', 'Gems/Form/Decorator/', Zend_Form::DECORATOR);
        $this->addPrefixPath('Gems_Form_Element',   'Gems/Form/Element/',   Zend_Form::ELEMENT);

        $this->addElementPrefixPath(GEMS_PROJECT_NAME_UC . '_Validate', GEMS_PROJECT_NAME_UC . '/Validate/', Zend_Form_Element::VALIDATE);
        $this->addElementPrefixPath('Gems_Form_Decorator',  'Gems/Form/Decorator/',  Zend_Form_Element::DECORATOR);
        $this->addElementPrefixPath('Gems_Filter',          'Gems/Filter/',          Zend_Form_Element::FILTER);
        $this->addElementPrefixPath('Gems_Validate',        'Gems/Validate/',        Zend_Form_Element::VALIDATE);

        $this->setDisableTranslator(true);
    }

    protected function _activateJQueryView(Zend_View_Interface $view = null)
    {
        if ($this->_no_jquery) {
            return;
        }

        if (null === $view) {
            $view = $this->getView();
            if (null === $view) {
                return;
            }
        }

        parent::_activateJQueryView($view);

        if (false === $view->getPluginLoader('helper')->getPaths('Gems_JQuery_View_Helper')) {
            $view->addHelperPath('Gems/JQuery/View/Helper', 'Gems_JQuery_View_Helper');
        }
    }

    /**
     * Change all elements into an autosubmit element
     *
     * Call only when $_autoSubmit is set
     *
     * @param mixed $element
     */
    private function _enableAutoSubmitElement($element)
    {
        if ($element instanceof Zend_Form || $element instanceof Zend_Form_DisplayGroup) {
            foreach ($element->getElements() as $sub) {
                $this->_enableAutoSubmitElement($sub);
            }
        } elseif ($element instanceof Gems_Form_AutosubmitElementInterface) {
            $element->enableAutoSubmit($this->_autosubmit);
        }
    }

    public function activateJQuery()
    {
        if ($this->_no_jquery) {
            parent::activateJQuery();
            ZendX_JQuery::enableForm($this);

            $this->addPrefixPath('Gems_JQuery_Form_Decorator', 'Gems/JQuery/Form/Decorator/', Zend_Form::DECORATOR);
            $this->addPrefixPath('Gems_JQuery_Form_Element', 'Gems/JQuery/Form/Element/', Zend_Form::ELEMENT);

            $this->_activateJQueryView();

            $this->_no_jquery = false;
        }
    }

    /**
     * Attach a css file to the form with form-specific css
     *
     * Optional media parameter can be used to determine media-type (print, screen etc)
     *
     * @param string $file
     * @param string $media
     */
    public function addCss($file, $media = '')
    {
    	$this->_css[$file] = $media;
    }

    /**
     * Add a new element
     *
     * $element may be either a string element type, or an object of type
     * Zend_Form_Element. If a string element type is provided, $name must be
     * provided, and $options may be optionally provided for configuring the
     * element.
     *
     * If a Zend_Form_Element is provided, $name may be optionally provided,
     * and any provided $options will be ignored.
     *
     * @param  string|Zend_Form_Element $element
     * @param  string $name
     * @param  array|Zend_Config $options
     * @throws Zend_Form_Exception on invalid element
     * @return Zend_Form (continuation pattern)
     */
    public function addElement($element, $name = null, $options = null)
    {
        parent::addElement($element, $name, $options);

        if ($this->isAutoSubmit()) {
            if (null !== $name) {
                $element = $this->getElement($name);
            }
            $this->_enableAutoSubmitElement($element);
        }

        return $this;
    }

    /**
     * Add a script to the head
     *
     * @param sring $script name of script, located in baseurl/js/
     * @return Gems_Form (continuation pattern)
     */
    public function addScript($script)
    {
    	if (is_array($this->_scripts) && in_array($script, $this->_scripts)) {
            return $this;
        }
    	$this->_scripts[] = $script;

        return $this;
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    { }

    /**
     * Allows the loader to set resources.
     *
     * @param string $name Name of resource to set
     * @param mixed $resource The resource.
     * @return boolean True if $resource was OK
     */
    public function answerRegistryRequest($name, $resource)
    {
        if (MUtil_Registry_Source::$verbose) {
            MUtil_Echo::r('Resource set: ' . get_class($this) . '->' . __FUNCTION__ .
                    '("' . $name . '", ' .
                    (is_object($resource) ? get_class($resource) : gettype($resource)) . ')');
        }
        $this->$name = $resource;

        return true;
    }

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required values are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        return true;
    }

    /**
     * Filters the names that should not be requested.
     *
     * Can be overriden.
     *
     * @param string $name
     * @return boolean
     */
    protected function filterRequestNames($name)
    {
        return '_' !== $name[0];
    }

    /**
     * Get the autosubmit arguments (if any)
     *
     * @return array or null
     */
    public function getAutoSubmit()
    {
        return $this->_autosubmit;
    }

    /**
     * Return form specific css
     *
     * @return array
     */
    public function getCss()
    {
    	return $this->_css;
    }

    /**
     * Return form specific javascript
     *
     * @return array
     */
	public function getScripts() {
    	return $this->_scripts;
    }

    /**
     * Allows the loader to know the resources to set.
     *
     * Returns those object variables defined by the subclass but not at the level of this definition.
     *
     * Can be overruled.
     *
     * @return array of string names
     */
    public function getRegistryRequests()
    {
        // MUtil_Echo::track(array_filter(array_keys(get_object_vars($this)), array($this, 'filterRequestNames')));
        return array_filter(array_keys(get_object_vars($this)), array($this, 'filterRequestNames'));
    }

    /**
     * Is this a form that autosubmits?
     *
     * @return boolean
     */
    public function isAutoSubmit() {
        return isset($this->_autosubmit);
    }

    /**
     * Change the form into an autosubmit form
     *
     * @param mixed $submitUrl Url as MUtil_Html_UrlArrayAttribute, array or string
     * @param mixed $targetId Id of html element whose content is replaced by the submit result: MUtil_Html_ElementInterface or string
     */
    public function setAutoSubmit($submitUrl, $targetId) {
        // Filter out elements passed by type
        $args = MUtil_Ra::args(func_get_args(),
            array(
                'submitUrl' => array('MUtil_Html_UrlArrayAttribute', 'is_array', 'is_string'),
                'targetId'  => array('MUtil_Html_ElementInterface', 'is_string'),
                ), null, MUtil_Ra::STRICT);

        if ($args['targetId'] instanceof MUtil_Html_ElementInterface) {
            if (isset($args['targetId']->id)) {
                $args['targetId'] = '#' . $args['targetId']->id;
            } elseif (isset($args['targetId']->class)) {
                $args['targetId'] = '.' . $args['targetId']->class;
            } else {
                $args['targetId'] = $args['targetId']->getTagName();
            }
        } else {
            $args['targetId'] = '#' . $args['targetId'];
        }
        $this->_autosubmit = $args;
        $this->_enableAutoSubmitElement($this);
        $this->activateJQuery();
    }
}