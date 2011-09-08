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
 * @subpackage Lazy
 */

class MUtil_Lazy_RepeatableFormElements extends MUtil_Lazy_Repeatable
{
    public $element;
    public $splitHidden = false;
    public $label;

    private $_hidden_elements;

    public function __construct(Zend_Form $form)
    {
        parent::__construct($form);

        // Enable access to the elements in this repeater using:
        // $this->element and $this->label.
        //
        // The other access method is: $this->{name of element renderer}
        $this->element = $this->_currentLazy;
        $this->label   = MUtil_Html::create('label', $this->_currentLazy);
    }

    public function __get($name)
    {
        // Form elements have few public properties, so usually we use this as a
        // shortcut for a decorator function, however, if the property exists
        // (and no Decorator with the same name exists) the property value will
        // be returned.
        return MUtil_Lazy::call(array($this, 'getDecorator'), ucfirst($name));
    }

    public function __getRepeatable()
    {
        $elements = iterator_to_array(parent::__getRepeatable());

        if ($this->splitHidden) {
            $filteredElements = array();
            $this->_hidden_elements = array();

            foreach ($elements as $element) {
                if ($element instanceof Zend_Form_Element_Hidden) {
                    $this->_hidden_elements[] = $element;
                } else {
                    $filteredElements[] = $element;
                }
            }

            return $filteredElements;

        } else {
            $this->_hidden_elements = array();
            return $elements;
        }
    }

    public function getDecorator($name)
    {
        if ($current = $this->__current()) {
            if ($decorator = $current->getDecorator($name)) {
                $decorator->setElement($current);
                return new MUtil_Html_Raw($decorator->render(''));
            }

            if (property_exists($current, $name)) {
                return $current->$name;
            }
        }

        return null;
    }

    public function getHidden()
    {
        if ($this->splitHidden) {
            if (! is_array($this->_hidden_elements)) {
                $this->__getRepeatable();
            }

            return $this->_hidden_elements;
        }

        return array();
    }

    public function getSplitHidden()
    {
        return $this->splitHidden;
    }

    public function setSplitHidden($value = true)
    {
        $this->splitHidden = $value;
        // $this->_repeater   = null; // Reset

        return $this;
    }
}