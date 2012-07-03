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
 * @version    $Id$
 * @package    Gems
 * @subpackage Form
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

/**
 * Base form class with extensions for correct load paths, autosubmit forms and registry use.
 *
 * @package    Gems
 * @subpackage Form
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */
class Gems_Form extends MUtil_Form implements MUtil_Registry_TargetInterface
{
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
     * If set this holds the url and targetid for the autosubmit
     *
     * @var array
     */
    protected $_autosubmit = null;

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
        // $this->addPrefixPath(GEMS_PROJECT_NAME_UC . '_Form_Decorator', GEMS_PROJECT_NAME_UC . '/Form/Decorator/', Zend_Form::DECORATOR);
        // $this->addPrefixPath(GEMS_PROJECT_NAME_UC . '_Form_Element',   GEMS_PROJECT_NAME_UC . '/Form/Element/',   Zend_Form::ELEMENT);
        parent::__construct($options);

        $this->addPrefixPath('Gems_Form_Decorator',  'Gems/Form/Decorator/',  Zend_Form::DECORATOR);
        $this->addPrefixPath('Gems_Form_Element',    'Gems/Form/Element/',    Zend_Form::ELEMENT);

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
     * Add a script to the head
     *
     * @param sring $script name of script, located in baseurl/js/
     * @return unknown_type
     */
    public function addScript($script) {
    	if (is_array($this->_scripts) && in_array($script,$this->_scripts)) return true;
    	$this->_scripts[] = $script;
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    { }
    
	public function getScripts() {
    	return $this->_scripts;
    }

    /**
     * Attach a css file to the form with form-specific css
     *
     * Optional media parameter can be used to determine media-type (print, screen etc)
     *
     * @param string $file
     * @param string $media
     */
    public function addCss($file, $media = '') {
    	$this->_css[$file] = $media;
    }

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

    public function getCss()
    {
    	return $this->_css;
    }

    public function getAutoSubmit()
    {
        return $this->_autosubmit;
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
     *
     * @param type $submitUrl
     * @param type $targetId
     */
    public function setAutoSubmit($submitUrl, $targetId) {
        // Filter out elements passed by type
        $args = MUtil_Ra::args(func_get_args(),
            array(
                'submitUrl' => array('MUtil_Html_UrlArrayAttribute', 'is_array', 'is_string'),
                'targetId'  => array('MUtil_Html_ElementInterface', 'is_string'),
                ), null, MUtil_Ra::STRICT);

        if ($args['targetId'] instanceof MUtil_Html_ElementInterface) {
            $args['targetId'] = isset($args['targetId']->id) ? '#' . $args['targetId']->id : (isset($args['targetId']->class) ? '.' . $args['targetId']->class: $args['targetId']->getTagName());
        } else {
            $args['targetId'] = '#' . $args['targetId'];
        }
        $this->_autosubmit = $args;
        $this->activateJQuery();
    }
}