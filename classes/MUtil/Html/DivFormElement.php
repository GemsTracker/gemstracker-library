<?php

/**
 * Copyright (c) 2014, Erasmus MC
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
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * A Div displayer using bootstrap element classes
 *
 * @package    MUtil
 * @subpackage Html
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.4
 */
class MUtil_Html_DivFormElement extends MUtil_Html_HtmlElement implements MUtil_Html_FormLayout
{
    /**
     * Can process form elements
     *
     * @var array
     */
    protected $_specialTypes = array(
        'Zend_Form' => 'setAsFormLayout',
        );

    /**
     * Should have content
     *
     * @var boolean The element is rendered even without content when true.
     */
    public $renderWithoutContent = false;

    public function __construct($arg_array = null)
    {
        $args = MUtil_Ra::args(func_get_args());

        parent::__construct('div', array('class' => 'form-group'), $args);
    }

    /**
     * Static helper function for creation, used by @see MUtil_Html_Creator.
     *
     * @param mixed $arg_array Optional MUtil_Ra::args processed settings
     * @return MUtil_Html_PFormElement
     */
    public static function divForm($arg_array = null)
    {
        $args = func_get_args();
        return new self($args);
    }

    /**
     * Apply this element to the form as the output decorator.
     *
     * @param Zend_Form $form
     * @param mixed $width The style.width content for the labels
     * @param array $order The display order of the elements
     * @param string $errorClass Class name to display all errors in
     * @return MUtil_Html_DlElement
     */
    public function setAsFormLayout(Zend_Form $form, $width = null, $order = array('label', 'element', 'description'), $errorClass = 'errors')
    {
        $this->_repeatTags = true;
        $prependErrors     = $errorClass;

        // Make a Lazy repeater for the form elements and set it as the element repeater
        $formrep = new MUtil_Lazy_RepeatableFormElements($form);
        $formrep->setSplitHidden(true); // These are treated separately
        $this->setRepeater($formrep);

        if (null === $width) {
            $attr = array();
        } else {
            $attr['style'] = array('display' => 'inline-block', 'width' => $width);
        }

        $inputGroup = null;

        // Place the choosen renderers
        foreach ($order as $renderer) {
            switch ($renderer) {
                case 'label':
                    $this->label($formrep->element, $attr); // Set label with optional width
                    break;

                case 'error':
                    $prependErrors = false;
                    // Intentional fall through

                case 'description':
                    $this->append($formrep->$renderer);
                    break;

                default:
                    if (! $inputGroup) {
                        $inputGroup = $this->div(array('class' => 'input-group'));
                    }
                    $inputGroup->append($formrep->$renderer);
            }
        }

        // Set this element as the form decorator
        $decorator = new MUtil_Html_ElementDecorator();
        $decorator->setHtmlElement($this);
        $decorator->setPrologue($formrep);  // Renders hidden elements before this element
        if ($prependErrors) {
            $decorator->setPrependErrors(MUtil_Html_ListElement::ul(array('class' => $errorClass, 'style' => array('margin-left' => $width))));
        }
        $form->setDecorators(array($decorator, 'AutoFocus', 'Form'));

        return $this;
    }

    /**
     * Apply this element to the form as the output decorator with automatically calculated widths.
     *
     * @param Zend_Form $form
     * @param float $factor To multiply the widest nummers of letters in the labels with to calculate the width in em at drawing time
     * @param array $order The display order of the elements
     * @return MUtil_Html_PFormElement
     */
    public function setAutoWidthFormLayout(Zend_Form $form, $factor = 1, array $order = array('label', 'element', 'description'))
    {
        // Lazy call becase the form might not be completed at this stage.
        return $this->setAsFormLayout($form, MUtil_Lazy::call(array('MUtil_Html_DlElement', 'calculateAutoWidthFormLayout'), $form, $factor), $order);
    }
}