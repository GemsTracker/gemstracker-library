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
 * @subpackage Html
 */

class MUtil_Html_DlElement extends MUtil_Html_HtmlElement implements MUtil_Html_FormLayout
{
    public $renderWithoutContent = false;

    protected $_allowedChildTags = array('dt', 'dd');

    protected $_appendString = "\n";

    protected $_specialTypes = array(
        'Zend_Form' => 'setAsFormLayout',
        );

    public function __construct($arg_array = null)
    {
        $args = MUtil_Ra::args(func_get_args());

        parent::__construct('dl', $args);
    }

    public function addItem($dt = null, $dd = null)
    {
        $ds = $this->addItemArray($dt, $dd);

        if (count($ds) > 1) {
            // Return all objects in a wrapper object
            // that makes sure they are all treated
            // the same way.
            return new MUtil_MultiWrapper($ds);
        }

        // Return first object only
        return reset($ds);
    }

    public function addItemArray($dt = null, $dd = null)
    {
        $ds = array();

        if ($dt) {
            if (self::alreadyIsA($dt, $this->_allowedChildTags)) {
                $this[] = $dt;
            } else {
                $dt = $this->dt($dt);
            }
            $ds['dt'] = $dt;
        }
        if ($dd) {
            if (self::alreadyIsA($dd, $this->_allowedChildTags)) {
                $this[] = $dd;
            } else {
                $dd = $this->dd($dd);
            }
            $ds['dd'] = $dd;
        }

        return $ds;
    }

    public static function calculateAutoWidthFormLayout(Zend_Form $form, $factor = 1)
    {
        static $last_form;
        static $last_factor;

        // No need to repeat the calculations for every element,
        // which would otherwise happen.
        if ($form === $last_form) {
            return $last_factor;
        }

        $maxwidth = 0;

        foreach ($form->getElements() as $element) {
            if ($decorator = $element->getDecorator('Label')) {
                $decorator->setElement($element);
                $len = strlen(strip_tags($decorator->getLabel()));

                if ($len > $maxwidth) {
                    $maxwidth = $len;
                }
            }
        }

        $last_form = $form;
        if ($maxwidth) {
            $last_factor = intval($factor * $maxwidth) . 'em';
        } else {
            // We need to return some usable value.
            $last_factor = 'auto';
        }

        return $last_factor;
    }


    public static function dl($arg_array = null)
    {
        $args = func_get_args();
        return new self($args);
    }

    public function dtDd($dt = null, $dd = null)
    {
        return $this->addItem($dt, $dd);
    }

    public function setAsFormLayout(Zend_Form $form, $width = null, array $order = array('element', 'errors', 'description'))
    {
        // Make a Lazy repeater for the form elements and set it as the element repeater
        $formrep = new MUtil_Lazy_RepeatableFormElements($form);
        $formrep->setSplitHidden(true); // These are treated separately
        $this->setRepeater($formrep);

        if (null === $width) {
            $attr = array();
        } else {
            $attr['style'] = array('width' => $width);
        }
        $this->dt()->label($formrep->element, $attr);  // Set label dt with optional width
        $dd = $this->dd();
        foreach ($order as $renderer) {
            $dd[] = $formrep->$renderer;
        }

        // $this->dd($formrep->element, ' ', $formrep->errors, ' ', $formrep->description);
        // $this->dd($formrep->element, $formrep->description, $formrep->errors);

        // Set this element as the form decorator
        $decorator = new MUtil_Html_ElementDecorator();
        $decorator->setHtmlElement($this);
        $decorator->setPrologue($formrep);  // Renders hidden elements before this element
        $form->setDecorators(array($decorator, 'AutoFocus', 'Form'));

        return $this;
    }


    public function setAutoWidthFormLayout(Zend_Form $form, $factor = 1, array $order = array('element', 'errors', 'description'))
    {
        // Lazy call becase the form might not be completed at this stage.
        return $this->setAsFormLayout($form, MUtil_Lazy::call(array(__CLASS__, 'calculateAutoWidthFormLayout'), $form, $factor), $order);
    }
}