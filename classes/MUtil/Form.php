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
 * @package    MUtil
 * @subpackage Form
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Extends a Zend_Form with automatic Dojo and JQuery activation,
 * MUtil_Html rendering integration and non-css stylesheet per
 * form (possibly automatically calculated) fixed label widths.
 *
 * @see MUtil_Html
 *
 * @package    MUtil
 * @subpackage Form
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class MUtil_Form extends Zend_Form
{
    /**
     * The order in which the element parts should be displayed
     * when using a fixed or dynamic label width.
     *
     * @var array
     */
    protected $_displayOrder = array('element', 'errors', 'description');

    /**
     * $var MUtil_HtmlElement
     */
    protected $_html_element;

    protected $_labelWidth;
    protected $_labelWidthFactor;
    protected $_no_dojo = true;
    protected $_no_jquery = true;

    protected $_Lazy = false;

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
        $this->addPrefixPath('MUtil_Form_Decorator', 'MUtil/Form/Decorator/', Zend_Form::DECORATOR);
        $this->addPrefixPath('MUtil_Form_Element',   'MUtil/Form/Element/',   Zend_Form::ELEMENT);

        $this->addElementPrefixPath('MUtil_Form_Decorator', 'MUtil/Form/Decorator',  Zend_Form_Element::DECORATOR);
        $this->addElementPrefixPath('MUtil_Validate',       'MUtil/Validate/',       Zend_Form_Element::VALIDATE);

        parent::__construct($options);
    }

    private function _activateDojoView(Zend_View_Interface $view = null)
    {
        if ($this->_no_dojo) {
            return;
        }

        if (null === $view) {
            $view = $this->getView();
            if (null === $view) {
                return;
            }
        }

        Zend_Dojo::enableView($view);
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

        ZendX_JQuery::enableView($view);

        if (false === $view->getPluginLoader('helper')->getPaths('MUtil_JQuery_View_Helper')) {
            $view->addHelperPath('MUtil/JQuery/View/Helper', 'MUtil_JQuery_View_Helper');
        }
    }

    public function activateDojo()
    {
        if ($this->_no_dojo) {
            Zend_Dojo::enableForm($this);

            $this->_activateDojoView();

            $this->_no_dojo = false;
        }
    }

    public function activateJQuery()
    {
        if ($this->_no_jquery) {
            ZendX_JQuery::enableForm($this);

            //$this->addPrefixPath('MUtil_JQuery_Form_Decorator', 'MUtil/JQuery/Form/Decorator/', Zend_Form::DECORATOR);
            $this->addPrefixPath('MUtil_JQuery_Form_Element', 'MUtil/JQuery/Form/Element/', Zend_Form::ELEMENT);

            $this->_activateJQueryView();

            $this->_no_jquery = false;
        }
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

        if (null === $name) {
            $name = $element->getName();
        } else {
            $element = $this->getElement($name);
        }
        if ($this->_no_dojo && ($element instanceof Zend_Dojo_Form_Element_Dijit)) {
            $this->activateDojo();
        }
        if ($this->_no_jquery && ($element instanceof ZendX_JQuery_Form_Element_UiWidget)) {
            $this->activateJQuery();
        }
        if ($element instanceof Zend_Form_Element_File) {
            $this->setAttrib('enctype', 'multipart/form-data');
        }
        $element->setDisableTranslator($this->translatorIsDisabled());

        return $this;
    }

    /**
     * The order in which the element parts should be displayed
     * when using a fixed or dynamic label width.
     *
     * @see setLabelWidth
     *
     * @return array Array containing element parts like 'element', 'errors' and 'description'
     */
    public function getDisplayOrder()
    {
        return $this->_displayOrder;
    }

    /**
     * Returns an Html element that is used to render the form contents.
     *
     * @return MUtil_Html_HtmlElement Or an equivalent class
     */
    public function getHtml()
    {
        if (! $this->_html_element) {
            foreach ($this->_decorators as $decorator) {
                if ($decorator instanceof MUtil_Html_ElementDecorator) {
                    break;
                }
            }
            if ($decorator instanceof MUtil_Html_ElementDecorator) {
                $this->_html_element = $decorator->getHtmlElement();
            } else {
                $this->setHtml();
            }
        }

        return $this->_html_element;
    }


    public function getLabelWidth()
    {
        return $this->_labelWidth;
    }

    public function getLabelWidthFactor()
    {
        return $this->_labelWidthFactor;
    }

    /**
     * Return true when the form is lazy
     *
     * @return boolean
     */
    public function isLazy()
    {
        return $this->_Lazy;
    }

    /**
     * Validate the form
     *
     * As it is better for translation utilities to set the labels etc. translated,
     * the MUtil default is to disable translation.
     *
     * However, this also disables the translation of validation messages, which we
     * cannot set translated. The MUtil form is extended so it can make this switch.
     *
     * @param  array   $data
     * @param  boolean $disableTranslateValidators Extra switch
     * @return boolean
     */
    public function isValid($data, $disableTranslateValidators = null)
    {
        if (null !== $disableTranslateValidators) {
            if ($disableTranslateValidators !== $this->translatorIsDisabled()) {
                $oldTranslations = $this->translatorIsDisabled();
                $this->setDisableTranslator($disableTranslateValidators);
            }
        }

        $valid = parent::isValid($data);

        if (isset($oldTranslations)) {
            $this->setDisableTranslator($oldTranslations);
        }

        return $valid;
    }

    /**
     * Load the default decorators
     *
     * @return void
     */
    public function loadDefaultDecorators()
    {
        if ($this->loadDefaultDecoratorsIsDisabled()) {
            return;
        }

        $decorators = $this->getDecorators();
        if (empty($decorators)) {
            $this->addDecorator('AutoFocus')
                 ->addDecorator('FormElements')
                 ->addDecorator('HtmlTag', array('tag' => 'dl', 'class' => 'zend_form'))
                 ->addDecorator('Form');
        }
    }

    /**
     * Indicate whether or not translation should be disabled
     *
     * Added cascading to elements
     *
     * @param  bool $flag
     * @return MUtil_Form
     */
    public function setDisableTranslator($flag)
    {
        $flag = (bool) $flag;
        if ($flag !== $this->translatorIsDisabled()) {
            parent::setDisableTranslator($flag);

            foreach ($this as $element) {
                $element->setDisableTranslator($flag);
            }
        }

        return $this;
    }

    /**
     * The order in which the element parts should be displayed
     * when using a fixed or dynamic label width.
     *
     * @see setLabelWidth
     *
     * @param array $order Array containing element parts like 'element', 'errors' and 'description'
     * @return MUtil_Form (continuation pattern)
     */
    public function setDisplayOrder(array $order)
    {
        $this->_displayOrder = $order;

        return $this;
    }

    /**
     * Sets the layout to the use of html elements
     *
     * @see MUtil_Html
     *
     * @param string $html HtmlTag for element or empty sequence when empty
     * @param string $args MUtil_Ra::args additional arguments for element
     * @return MUtil_Form (continuation pattern)
     */
    public function setHtml($html = null, $args = null)
    {
        $options = MUtil_Ra::args(func_get_args(), 1);

        if ($html instanceof MUtil_Html_ElementInterface) {
            if ($options) {
                foreach ($options as $name => $option) {
                    if (is_int($name)) {
                        $html[] = $option;
                    } else {
                        $html->$name = $option;
                    }
                }
            }
        } elseif (null == $html) {
            $html = new MUtil_Html_Sequence($options);
        } else {
            $html = MUtil_Html::createArray($html, $options);
        }

        if ($html instanceof MUtil_Html_FormLayout) {
            $html->setAsFormLayout($this);
        } else {
            // Set this element as the form decorator
            $decorator = new MUtil_Html_ElementDecorator();
            $decorator->setHtmlElement($html);
            // $decorator->setPrologue($formrep); // Renders hidden elements before this element
            $this->setDecorators(array($decorator, 'AutoFocus', 'Form'));
        }

        $this->_html_element = $html;

        return $this;
    }

    /**
     * Render the element labels with a fixed width
     *
     * @param mixed $width The style.width content for the labels
     * @return MUtil_Form (continuation pattern)
     */
    public function setLabelWidth($width)
    {
        $this->_labelWidth = $width;

        $layout = new MUtil_Html_DlElement();
        $layout->setAsFormLayout($this, $width, $this->getDisplayOrder());

        $this->_html_element = $layout;

        return $this;
    }

    /**
     * Render elements with an automatically calculated label width, by multiplying the maximum number of
     * characters in a label with this factor.
     *
     * @param float $factor To multiply the widest nummers of letters in the labels with to calculate the width in em at drawing time
     * @return MUtil_Form (continuation pattern)
     */
    public function setLabelWidthFactor($factor)
    {
        $this->_labelWidthFactor = $factor;

        $layout = new MUtil_Html_DlElement();
        $layout->setAutoWidthFormLayout($this, $factor, $this->getDisplayOrder());

        $this->_html_element = $layout;

        return $this;
    }

    /**
     * Is the form Lazy or can it be rendered normally?
     *
     * @param boolean $lazy
     */
    public function setLazy($lazy = false)
    {
        $this->_Lazy = (bool) $lazy;
    }

    /**
     * Set view object
     *
     * @param  Zend_View_Interface $view
     * @return Zend_Form
     */
    public function setView(Zend_View_Interface $view = null)
    {
        if ($view) {
            if (! $this->_no_dojo) {
                $this->_activateDojoView($view);
            }
            if (! $this->_no_jquery) {
                $this->_activateJQueryView($view);
            }
        }

        return parent::setView($view);
    }
}