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
 *
 * @package    MUtil
 * @subpackage Form_Decorator
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Display a form in a table decorator.
 *
 * @package    MUtil
 * @subpackage Form_Decorator
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class MUtil_Form_Decorator_Table extends \Zend_Form_Decorator_ViewHelper
{
    /**
     *
     * @var array of \Zend_Form_Decorator constructors or obhjects
     */
    protected $_cellDecorators;

    /**
     * Change the current decorators
     *
     * @param \Zend_Form_Element $element
     * @param array $decorators
     */
    private function _applyDecorators(\Zend_Form_Element $element, array $decorators)
    {
        $element->clearDecorators();
        foreach ($decorators as $decorator) {
            call_user_func_array(array($element, 'addDecorator'), $decorator);
        }
    }

    /**
     * Loads Cell decorators if needed.
     * @return array of \Zend_Form_Decorator constructors or obhjects
     */
    public function getCellDecorators()
    {
        if (! $this->_cellDecorators) {
            $this->loadDefaultCellDecorators();
        }

        return $this->_cellDecorators;
    }

    /**
     *
     * @return array of \Zend_Form_Decorator constructors or obhjects
     */
    public function loadDefaultCellDecorators()
    {
        if (! $this->_cellDecorators) {
            /* $this->_cellDecorators = array(
                array('ViewHelper'),
                array('Errors'),
                array('Description', array('tag' => 'p', 'class' => 'description'))
                ); */
            $this->_cellDecorators = array('ViewHelper', 'Errors');
        }
        return $this->_cellDecorators;
    }

    /**
     * Render the element
     *
     * @param  string $content Content to decorate
     * @return string
     */
    public function render($content)
    {
        $element = $this->getElement();
        if ((null === $element) ||
            (null === ($view = $element->getView()))) {
            return $content;
        }

        $cellDecorators = $this->getCellDecorators();

        $table = new \MUtil_Html_TableElement();
        $table->setOnEmpty(array(new \MUtil_Html_Raw('&hellip;'), 'style' => 'text-align: center;'));

        if ($element instanceof \MUtil_Form_Element_Table) {

            $subforms = $element->getSubForms();
            $table->id = $element->getName();
            $table->class = $element->getAttrib('class');
            if ($subforms) {
                $firstform  = reset($subforms);
            } else {
                $firstform = $element->getSubForm();
            }
        } elseif ($element instanceof \Zend_Form)  {
            $cellDecorators = null;
            $firstform  = $element;
            $subforms = array($element);
        }

        if (isset($firstform)) {

//            $hasDescriptions = false;
            foreach ($firstform->getElements() as $headerelement) {
                if (! $headerelement instanceof \Zend_Form_Element_Hidden) {
                    if (! $subforms) {
                       $headerelement->setAttrib('id', $element->getName());
                    }
                    $last_cell = $table->th(array('title' => $headerelement->getDescription()))
                            ->label($headerelement);

//                    if ($headerelement->getDescription()) {
//                        $hasDescriptions = true;
//                    }
                }
            }
//            if ($hasDescriptions) {
//                foreach ($firstform->getElements() as $headerelement) {
//                    if (! $headerelement instanceof \Zend_Form_Element_Hidden) {
//                        if ($headerelement->getDescription()) {
//                            $table->tf()->inputOnly($headerelement, 'Description');
//                        } else {
//                            $table->tf();
//                        }
//                    }
//                }
//            }

            $hidden = array();
            if ($subforms) {
                foreach ($subforms as $subform) {
                    $table->tr();
                    foreach ($subform->getElements() as $subelement) {
                        if ($subelement instanceof \Zend_Form_Element_Hidden) {
                            $this->_applyDecorators($subelement, array(array('ViewHelper')));
                            $hidden[] = $subelement;
                        } else {
                            $last_cell = $table->td($hidden, array('title' => $subelement->getDescription()));
                            if ($cellDecorators) {
                                $last_cell->inputOnlyArray($subelement, $cellDecorators);
                            } else {
                                $last_cell[] = $subelement;
                            }
                            $hidden = array();
                        }
                    }
                }
                if ($hidden) {
                    $last_cell[] = $hidden;
                }
            }

        } elseif ($element instanceof \Zend_Form_DisplayGroup) {
            throw new Exception('Rendering of \Zend_Form_DisplayGroup in ' . __CLASS__ . ' not yet implemented.');

        } elseif ($element instanceof \Zend_Form_Element) {
            throw new Exception('Rendering of \Zend_Form_Element in ' . __CLASS__ . ' not yet implemented.');
            // $table->addColumn($element->renderViewHelper(), $element->renderLabel());

        } else {
            $table->td($element);

        }

        return $table->render($view);
    }

    /**
     * Set a single option
     *
     * @param  string $key
     * @param  mixed $value
     * @return \Zend_Form_Decorator_Interface
     */
    public function setOption($key, $value)
    {
        switch ($key) {
            case 'cellDecorator':
                $value = $this->getCellDecorators() + array($value);

            case 'cellDecorators':
                $this->_cellDecorators = $value;
                break;

            default:
                parent::setOption($key, $value);
                break;
        }

        return $this;
    }

    /**
     * Set decorator options from an array
     *
     * @param  array $options
     * @return \Zend_Form_Decorator_Interface
     */
    public function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            $this->setOption($key,  $value);
        }

        return $this;
    }
}

