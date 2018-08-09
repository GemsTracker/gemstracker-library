<?php

/**
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
 * @since      Class available since version 1.0
 */
class Gems_Form extends \MUtil_Form
{
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
        parent::__construct($options);

        $this->addPrefixPath(GEMS_PROJECT_NAME_UC . '_Form_Decorator', GEMS_PROJECT_NAME_UC . '/Form/Decorator/', \Zend_Form::DECORATOR);
        $this->addPrefixPath(GEMS_PROJECT_NAME_UC . '_Form_Element',   GEMS_PROJECT_NAME_UC . '/Form/Element/',   \Zend_Form::ELEMENT);
        $this->addPrefixPath('Gems_Form_Decorator', 'Gems/Form/Decorator/', \Zend_Form::DECORATOR);
        $this->addPrefixPath('Gems_Form_Element',   'Gems/Form/Element/',   \Zend_Form::ELEMENT);

        $this->addElementPrefixPath(GEMS_PROJECT_NAME_UC . '_Validate', GEMS_PROJECT_NAME_UC . '/Validate/', \Zend_Form_Element::VALIDATE);
        $this->addElementPrefixPath('Gems_Form_Decorator',  'Gems/Form/Decorator/',  \Zend_Form_Element::DECORATOR);
        $this->addElementPrefixPath('Gems_Filter',          'Gems/Filter/',          \Zend_Form_Element::FILTER);
        $this->addElementPrefixPath('Gems_Validate',        'Gems/Validate/',        \Zend_Form_Element::VALIDATE);

        $this->setDisableTranslator(true);

        $this->activateBootstrap();
    }


    public function activateBootstrap() {
        if (\MUtil_Bootstrap::enabled() === true) {
            parent::activateBootstrap();
        }
    }

    protected function _activateJQueryView(\Zend_View_Interface $view = null) {
        parent::_activateJQueryView($view);

        if (null === $view) {
            $view = $this->getView();
        }

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
        if ($element instanceof \Zend_Form || $element instanceof \Zend_Form_DisplayGroup) {
            foreach ($element->getElements() as $sub) {
                $this->_enableAutoSubmitElement($sub);
            }
        } elseif ($element instanceof \Gems_Form_AutosubmitElementInterface) {
            $element->enableAutoSubmit($this->_autosubmit);
        }
    }

    /**
     * Activate JQuery for this form
     *
     * @return \MUtil_Form (continuation pattern)
     */
    public function activateJQuery()
    {
        if ($this->_no_jquery) {
            parent::activateJQuery();

            $this->addPrefixPath('Gems_JQuery_Form_Decorator', 'Gems/JQuery/Form/Decorator/', \Zend_Form::DECORATOR);
            $this->addPrefixPath('Gems_JQuery_Form_Element', 'Gems/JQuery/Form/Element/', \Zend_Form::ELEMENT);
        }

        return $this;
    }

    /**
     * Add a new element
     *
     * $element may be either a string element type, or an object of type
     * \Zend_Form_Element. If a string element type is provided, $name must be
     * provided, and $options may be optionally provided for configuring the
     * element.
     *
     * If a \Zend_Form_Element is provided, $name may be optionally provided,
     * and any provided $options will be ignored.
     *
     * @param  string|\Zend_Form_Element $element
     * @param  string $name
     * @param  array|\Zend_Config $options
     * @throws \Zend_Form_Exception on invalid element
     * @return \Zend_Form (continuation pattern)
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
     * Get the autosubmit arguments (if any)
     *
     * @return array or null
     */
    public function getAutoSubmit()
    {
        return $this->_autosubmit;
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
     * @param mixed $submitUrl Url as \MUtil_Html_UrlArrayAttribute, array or string
     * @param mixed $targetId Id of html element whose content is replaced by the submit result: \MUtil_Html_ElementInterface or string
     * @param boolean $selective When true autosubmit is applied only to elements with the CSS class autosubmit
     */
    public function setAutoSubmit($submitUrl, $targetId, $selective = false)
    {
        // Filter out elements passed by type
        $args = \MUtil_Ra::args(func_get_args(),
            array(
                'submitUrl' => array('MUtil_Html_UrlArrayAttribute', 'is_array', 'is_string'),
                'targetId'  => array('MUtil_Html_ElementInterface', 'is_string'),
                ), null, \MUtil_Ra::STRICT);

        if (isset($args['targetId'])) {
            if ($args['targetId'] instanceof \MUtil_Html_ElementInterface) {
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
        }
        if ($selective) {
            $args['selective'] = true;
        }

        $this->_autosubmit = $args;
        $this->_enableAutoSubmitElement($this);
        $this->activateJQuery();
    }
}