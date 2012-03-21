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
 * 
 * @package    MUtil
 * @subpackage Form
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class MUtil_Form extends Zend_Form
{
    protected $_displayOrder = array('element', 'errors', 'description');
    protected $_html_element;
    protected $_labelWidth;
    protected $_labelWidthFactor;
    protected $_no_dojo = true;
    protected $_no_jquery = true;

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

    private function _activateJQueryView(Zend_View_Interface $view = null)
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

            $this->addPrefixPath('MUtil_JQuery_Form_Decorator', 'MUtil/JQuery/Form/Decorator/', Zend_Form::DECORATOR);
            $this->addPrefixPath('MUtil_JQuery_Form_Element', 'MUtil/JQuery/Form/Element/', Zend_Form::ELEMENT);

            $this->_activateJQueryView();

            $this->_no_jquery = false;
        }
    }

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

    public function getDisplayOrder()
    {
        return $this->_displayOrder;
    }

    /**
     *
     * @return MUtil_Html_HtmlElement
     */
    public function getHtml()
    {
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

            foreach ($this->getElements() as $element) {
                $element->setDisableTranslator($flag);
            }
        }

        return $this;
    }

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

    public function setLabelWidth($width)
    {
        $this->_labelWidth = $width;

        $layout = new MUtil_Html_DlElement();
        $layout->setAsFormLayout($this, $width, $this->getDisplayOrder());

        $this->_html_element = $layout;

        return $this;
    }

    public function setLabelWidthFactor($factor)
    {
        $this->_labelWidthFactor = $factor;

        $layout = new MUtil_Html_DlElement();
        $layout->setAutoWidthFormLayout($this, $factor, $this->getDisplayOrder());

        $this->_html_element = $layout;

        return $this;
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