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
 * @subpackage Html
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * This class handles the rendering of input elements.
 *
 * If a \Zend_Form object is passed as first parameter, then it is rendered appropriately.
 * Otherwise the constructor tries to handle it as an attempt to create a raw HtmlElement
 * input element.
 *
 * @package    MUtil
 * @subpackage Html
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class MUtil_Html_InputRenderer implements \MUtil_Html_HtmlInterface
{
    const MODE_COMPLETE = 'complete';
    const MODE_DISPLAY_GROUP = 'displayGroup';
    const MODE_ELEMENT = 'except';
    const MODE_EXCEPT = 'except';
    const MODE_FORM = 'form';
    const MODE_HTML = 'html';
    const MODE_ONLY = 'only';
    const MODE_UNTIL = 'until';
    const MODE_UPTO = 'upto';

    const ARGUMENT_ERROR = 'Invalid argument of type %s in %s. Only \Zend_Form and \Zend_Form_Element objects are allowed and \MUtil_Lazy_LazyInterface objects as long as they devolve to \Zend_Form or \Zend_Form_Element.';

    private $_decorators;
    private $_element;
    private $_mode;

    /**
     *
     * @param \Zend_Form|\Zend_Form_Element|\MUtil_Lazy_LazyInterface $element
     * @param $mode One of the class MODE_ constants
     * @param array of string|array|\Zend_Form_Decorator_Interface $decorators Optional An arrya that contains values
     * that are either a string value that identifies an existing decorator or an array that creates an new decorator
     * or a decorator instance.
     */
    public function __construct($element, $mode = self::MODE_ELEMENT, array $decorators = array())
    {
        if (($element instanceof \Zend_Form_Element) ||
                ($element instanceof \Zend_Form_DisplayGroup) ||
                ($element instanceof \Zend_Form) ||
                ($element instanceof \MUtil_Lazy_LazyInterface)) {

            switch ($mode) {
                case self::MODE_COMPLETE:
                case self::MODE_DISPLAY_GROUP:
                case self::MODE_ELEMENT:
                case self::MODE_FORM:
                    if ($decorators) {
                        throw new \MUtil_Html_HtmlException('Invalid mode for ' . __CLASS__ .
                                ' constructor. With decorators the mode argument ' . $mode . ' is not allowed.');
                    }
                    break;

                case self::MODE_EXCEPT:
                case self::MODE_ONLY:
                case self::MODE_UNTIL:
                case self::MODE_UPTO:
                    if (! $decorators) {
                        throw new \MUtil_Html_HtmlException('Invalid mode ' . $mode . ' for ' . __CLASS__ .
                                ' constructor. Without decorators the only allowed mode argument is ' .
                                self::MODE_COMPLETE . '.');
                    }
                    break;

                default:
                    throw new \MUtil_Html_HtmlException('Unknown mode ' . $mode . ' for ' . __CLASS__ .
                            ' constructor.');
            }
            $this->_element    = $element;
            $this->_decorators = $decorators;
            $this->_mode       = $mode;

        } else {
            if (self::MODE_ELEMENT === $mode) {
                // Was the second argument not specified?
                // Then the arguments should be passed in $element.
                $args = $element;
            } else {
                // Use all args
                $args = func_get_args();
            }
            if (is_array($args)) {
                // Treat this as a standard Html Element
                $this->_element = new \MUtil_Html_HtmlElement('input', $args);
                $this->_mode = self::MODE_HTML;

                // \MUtil_Echo::track($args);
             } else {
                throw new \MUtil_Html_HtmlException(sprintf(
                        self::ARGUMENT_ERROR,
                        (is_object($element) ? get_class($element) : gettype($element)),
                        __CLASS__ . ' constructor'
                        ));
            }
        }
    }

    private static function _checkElement($element, $function)
    {
        $element = \MUtil_Lazy::rise($element);

        if (($element instanceof \Zend_Form_Element) ||
            ($element instanceof \Zend_Form_DisplayGroup) ||
            ($element instanceof \Zend_Form)) {

            return $element;
        }

        throw new \MUtil_Html_HtmlException(sprintf(self::ARGUMENT_ERROR, get_class($element), __CLASS__ . '::' .
                $function . '()'));
    }

    private static function _throwStopError($element, $decorators, $function)
    {
        $stoppers = '';
        foreach ($decorators as $until_decorator) {
            $stoppers .= ', ';
            if (is_string($until_decorator)) {
                $stoppers .= $until_decorator;
            } else {
                $stoppers .= get_class($until_decorator);
            }
        }
        if ($stoppers) {
            $start = 'None of the stopping decorators found';
            $stoppers  = "<br/>\n<strong>Stopping decorators specified:</strong> " . substr($stoppers, 2);
        } else {
            $start = 'No stopping decorators specified for';
            $stoppers = '';
        }

        $found = '';
        foreach ($element->getDecorators() as $name => $decorator) {
            $found .= ', ' . $name;
        }
        if ($found) {
            $found = "<br/>\n<strong>Decorators found:</strong> " . substr($found, 2);
        } else {
            $found = "<br/>\nNo decorators found in element.";
        }

        $message = $start . ' rendering element <strong>' .
            $element->getName() . '</strong> of type ' . get_class($element) .
            ' in ' . __CLASS__ . '::' . $function . "().<br>\n" . $stoppers . $found;

        // \MUtil_Echo::r($message);
        throw new \MUtil_Html_HtmlException($message);

    }

    public static function input($element)
    {
        if ($element instanceof \Zend_Form) {
            return self::inputForm($element);
        }

        if ($element instanceof \Zend_Form_DisplayGroup) {
            return self::inputDisplayGroup($element);
        }

        // Assume all lazy's to be elements (should be rare in any case.
        return self::inputElement($element);
    }


    public static function inputComplete($element)
    {
        return new self($element, self::MODE_COMPLETE);
    }


    public static function inputDescription($element)
    {
        return new self($element, self::MODE_ONLY, array('Description'));
    }


    public static function inputDisplayGroup($element)
    {
        return new self($element, self::MODE_DISPLAY_GROUP);
    }


    public static function inputElement($element)
    {
        return new self($element, self::MODE_ELEMENT);
    }


    public static function inputErrors($element)
    {
        return new self($element, self::MODE_ONLY, array('Errors'));
    }


    public static function inputExcept($element, $decorator_array)
    {
        $args = func_get_args();
        $decorators = array_slice($args, 1);

        return new self($element, self::MODE_EXCEPT, $decorators);
    }


    public static function inputForm($element)
    {
        return new self($element, self::MODE_FORM);
    }


    public static function inputLabel($arg_array = array())
    {
        $args = func_get_args();
        return new \MUtil_Html_LabelElement('label', $args);
    }


    public static function inputOnly($element, $decorator_array)
    {
        $args = func_get_args();
        $decorators = array_slice($args, 1);

        return new self($element, self::MODE_ONLY, $decorators);
    }


    public static function inputOnlyArray($element, array $decorators)
    {
        return new self($element, self::MODE_ONLY, $decorators);
    }


    public static function inputUntil($element, $decorator_array)
    {
        $args = func_get_args();
        $decorators = array_slice($args, 1);

        return new self($element, self::MODE_UNTIL, $decorators);
    }


    public static function inputUpto($element, $decorator_array)
    {
        $args = func_get_args();
        $decorators = array_slice($args, 1);

        return new self($element, self::MODE_UPTO, $decorators);
    }


    public function render(\Zend_View_Abstract $view)
    {
        switch ($this->_mode) {
            case self::MODE_COMPLETE:
                return self::renderComplete($view, $this->_element);

            case self::MODE_DISPLAY_GROUP:
                return self::renderDisplayGroup($view, $this->_element);

            case self::MODE_ELEMENT:
                return self::renderElement($view, $this->_element);

            case self::MODE_EXCEPT:
                return self::renderExcept($view, $this->_element, $this->_decorators);

            case self::MODE_FORM:
                return self::renderForm($view, $this->_element);

            case self::MODE_HTML:
                return $this->_element->render($view);

            case self::MODE_ONLY:
                return self::renderOnly($view, $this->_element, $this->_decorators);

            case self::MODE_UNTIL:
                return self::renderUntil($view, $this->_element, $this->_decorators);

            case self::MODE_UPTO:
                return self::renderUpto($view, $this->_element, $this->_decorators);

            // default: Not needed, checked in constructor
        }
    }

    public static function renderComplete(\Zend_View_Abstract $view, $element)
    {
        $element = self::_checkElement($element, __FUNCTION__);
        return $element->render($view);
    }

    public static function renderDisplayGroup(\Zend_View_Abstract $view, \Zend_Form_DisplayGroup $displayGroup)
    {
        return self::renderUntil($view, $displayGroup,
            array('Zend_Form_Decorator_Fieldset'));
        return self::renderOnly($view, $displayGroup,
            array('Zend_Form_Decorator_FormElements', 'Zend_Form_Decorator_Fieldset'));
    }

    public static function renderElement(\Zend_View_Abstract $view, $element)
    {
        return self::renderUntil($view, $element, array(
            'Zend_Form_Decorator_ViewHelper',
            'Zend_Form_Decorator_File',
            'MUtil_Form_Decorator_Table',
            'MUtil_Form_Decorator_Subforms'
            ));
    }

    public static function renderExcept(\Zend_View_Abstract $view, $element, array $except_decorators)
    {
        $element = self::_checkElement($element, __FUNCTION__);
        $element->setView($view);

        $content = '';
        foreach ($element->getDecorators() as $name => $decorator) {
            $render = true;

            foreach ($except_decorators as $except_decorators) {
                if (($except_decorators == $name) || ($decorator instanceof $except_decorators)) {
                    $render = false;
                    break;
                }
            }

            if ($render) {
                $decorator->setElement($element);
                $content = $decorator->render($content);
            }
        }

        return $content;
    }

    public static function renderForm(\Zend_View_Abstract $view, \Zend_Form $form)
    {
        if ($form instanceof \MUtil_Form && $form->isLazy()) {
            return self::renderUntil($view, $form, array('Zend_Form_Decorator_Form'));
        } else {
            return self::renderComplete($view, $form);
        }
    }

    public static function renderOnly(\Zend_View_Abstract $view, $element, array $decorators)
    {
        $element = self::_checkElement($element, __FUNCTION__);
        $element->setView($view);

        $content = '';
        foreach ($decorators as $decoratorinfo) {

            if ($decoratorinfo instanceof \Zend_Form_Decorator_Interface) {
                $decorator = $decoratorinfo;

            } else {
                if (is_array($decoratorinfo)) {
                    $decoratorname = array_shift($decoratorinfo);
                    if (is_array(reset($decoratorinfo))) {
                        $decoratoroptions = array_shift($decoratorinfo);
                    } else {
                        $decoratoroptions = $decoratorinfo;
                    }
                    $element->addDecorator($decoratorname, $decoratoroptions);

                } else {
                    $decoratorname = $decoratorinfo;
                }

                $decorator = $element->getDecorator($decoratorname);
            }

            if ($decorator) {
                $decorator->setElement($element);
                $content = $decorator->render($content);
            }
        }

        return $content;
    }

    public static function renderUntil(\Zend_View_Abstract $view, $element, array $until_decorators)
    {
        $element = self::_checkElement($element, __FUNCTION__);
        $element->setView($view);

        $content = '';
        foreach ($element->getDecorators() as $name => $decorator) {
            $decorator->setElement($element);
            $content = $decorator->render($content);

            foreach ($until_decorators as $until_decorator) {
                if (($until_decorator == $name) || ($decorator instanceof $until_decorator)) {
                    // \MUtil_Echo::r('<strong>' . $element->getName() . ', ' . $until_decorator . '</strong>' . htmlentities($content));
                    return $content;
                }
            }
        }

        self::_throwStopError($element, $until_decorators, __FUNCTION__);
    }

    public static function renderUpto(\Zend_View_Abstract $view, $element, array $until_decorators)
    {
        $element = self::_checkElement($element, __FUNCTION__);
        $element->setView($view);

        $content = '';
        foreach ($element->getDecorators() as $name => $decorator) {
            foreach ($until_decorators as $until_decorator) {
                if (($until_decorator == $name) || ($decorator instanceof $until_decorator)) {
                    return $content;
                }
            }

            $decorator->setElement($element);
            $content = $decorator->render($content);
        }

        self::_throwStopError($element, $until_decorators, __FUNCTION__);
    }
}
