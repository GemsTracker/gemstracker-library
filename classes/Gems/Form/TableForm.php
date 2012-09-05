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
 * Short description of file
 *
 * @package    Gems
 * @subpackage Form
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Render a Form as a table just by modifying the decorators
 *
 * @package    Gems
 * @subpackage Form
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Form_TableForm extends Gems_Form
{
    /**
     * Whether or not form elements are members of an array
     * @var bool
     */
    protected $_isArray = false;

    private $_alternate = null;

    /**
     *
     * @param string $content
     * @param Zend_Form_Element $element
     * @param array $options
     * @return string
     */
    public static function doRowElement($content, $element, array $options)
    {
        return self::doRow($content, $element->getLabel(), $options['class'], $element);
        
        return $content;
    }

    public static function doRowDisplayGroup($content, $element, array $options)
    {     
        return self::doRow($content, $element->getDescription(), $options['class'], $element);
    }

    public static function doRow($content, $label, $class, $element) {
        if ($element->getAttrib('tooltip')) {
            $dec = new Gems_Form_Decorator_Tooltip();
            $dec->setElement($element);
            $label .= $dec->render('');
        }
        if ($element instanceof Zend_Form_Element) {
            $style = $element->isRequired() ? 'required' : 'optional';
            $labelClass = sprintf(' class="%s"', $style);
        } else {
            $labelClass = '';
        }
        $content = sprintf('<tr class="%s"><td class="label"><label for="%s"%s>%s</label></td><td class="element">%s</td></tr>', $class, $element->getName(), $labelClass, $label, $content);
        return $content;
    }

    protected function _fixDecoratorDisplayGroup(&$element)
    {
        // Display group
        $element->setDecorators(
            array('FormElements',
            array('Callback',  array('callback' => array($this, 'doRowDisplayGroup'), 'class' => $this->_alternate . ' ' . $element->getName(), 'placement'=>false))
            
                /*
            array('FormElements',
            array(array('data' => 'HtmlTag'), array('tag'   => 'td', 'class' => 'element')),
            array(array('labelCellClose' => 'HtmlTag'), array('tag'       => 'td', 'placement' => Zend_Form_Decorator_Abstract::PREPEND, 'closeOnly' => true)),
            'Tooltip',
            array('Description', array('tag'       => 'label', 'class'     => 'optional', 'placement' => Zend_Form_Decorator_Abstract::PREPEND, 'escape'    => false)),
            array(array('labelCellOpen' => 'HtmlTag'), array('tag'       => 'td', 'class'     => 'label', 'placement' => Zend_Form_Decorator_Abstract::PREPEND, 'openOnly'  => true)),
            array(array('row' => 'HtmlTag'), array('tag'   => 'tr', 'class' => $this->_alternate . ' ' . $element->getName() . ' ' . $element->getAttrib('class')))
                 */
        ));

        //Now add the right decorators to the elements
        $groupElements = $element->getElements();
        foreach ($groupElements as $groupElement) {
            $dec1 = $this->_getImportantDecorator($groupElement);

            $decorators = array(array('Description', array('class' => 'description')),
                'Errors',
                'Tooltip',
            );

            //If we want to see the individual fields labels, do so:
            if ($element->getAttrib('showLabels') === true) {
                if ($groupElement instanceof Zend_Form_Element_Checkbox) {
                    $decorators[] = array('Label', array('escape'    => false, 'placement' => Zend_Form_Decorator_Label::APPEND));
                } else {
                    $decorators[] = array('Label', array('escape' => false));
                }
            }

            //Apply final class and id to allow for custom styling
            $decorators[] = array(array('labelCell' => 'HtmlTag'), array('tag'   => 'div', 'class' => 'tab-displaygroup', 'id'    => $groupElement->getName() . '_cont'));

            if (!is_null($dec1))
                array_unshift($decorators, $dec1);
            $groupElement->setDecorators($decorators);
        }
    }

    protected function _fixDecoratorElement(&$element)
    {
        $dec1       = $this->_getImportantDecorator($element);
        $decorators = array(
            array('Description', array('class' => 'description')),
            'Errors',
            array('Callback',  array('callback' => array($this, 'doRowElement'), 'class' => $this->_alternate . ' ' . $element->getName(), 'placement'=>false))

            //array('Description', array('class' => 'description')),
            //'Errors',
            //array(array('data' => 'HtmlTag'), array('tag'   => 'td', 'class' => 'element')),
            //array(array('labelCellClose' => 'HtmlTag'), array('tag'       => 'td', 'placement' => Zend_Form_Decorator_Abstract::PREPEND, 'closeOnly' => true)),
            //'Tooltip',
            //array('Label', array('escape' => false)),
            //array(array('labelCellOpen' => 'HtmlTag'), array('tag'       => 'td', 'class'     => 'label', 'placement' => Zend_Form_Decorator_Abstract::PREPEND, 'openOnly'  => true)),
            //array(array('row' => 'HtmlTag'), array('tag'   => 'tr', 'class' => $this->_alternate . ' ' . $element->getName()))
        );
        if (!is_null($dec1)) {
            array_unshift($decorators, $dec1);
        }
        $element->setDecorators($decorators);
    }

    protected function _fixDecoratorHiddenSubmit(&$element)
    {
        // No label and tooltip
        $rowOptions = array(
            'tag'       => 'tr',
            'class'     => $element->getName()
        );

        // Don't display if hidden
        if ($element instanceof Zend_Form_Element_Hidden) {
            $rowOptions['style'] = 'display:none;';
        }
        
        $decorators = array(
            'ViewHelper',
            array('Description', array('class' => 'description')),
            'Errors',
            array(array('data' => 'HtmlTag'), array('tag'   => 'td', 'class' => 'element')),
            array(array('labelCellClose' => 'HtmlTag'), array('tag'       => 'td', 'placement' => Zend_Form_Decorator_Abstract::PREPEND, 'closeOnly' => true)),
            'Tooltip',
            array(array('labelCellOpen' => 'HtmlTag'), array('tag'       => 'td', 'class'     => 'label', 'placement' => Zend_Form_Decorator_Abstract::PREPEND, 'openOnly'  => true)),
            array(array('row' => 'HtmlTag'), $rowOptions)
        );
        $element->setDecorators($decorators);
    }

    protected function _fixDecoratorHtml(&$element)
    {
        // Display with colspan = 2
        $decorators = array(
            'ViewHelper',
            array('Description', array('class' => 'description')),
            'Errors',
            'Tooltip',
            array('Label', array('escape' => false)),
            array(array('labelCell' => 'HtmlTag'), array('tag'     => 'td', 'class'   => 'label', 'colspan' => 2)),
            array(array('row' => 'HtmlTag'), array('tag'   => 'tr', 'class' => $this->_alternate . ' ' . $element->getAttrib('class') . ' ' . $element->getName()))
        );

        $element->setDecorators($decorators);
    }

    /**
     * Get a ViewHelper or ZendX decorator to add in front of the decorator chain
     *
     * @param Zend_Form_Element $element
     * @return null|Zend_Form_Decorator_Abstract
     */
    private function _getImportantDecorator($element)
    {
        $class = get_class($element);

        if (strpos($class, 'JQuery')) {
            $dec = $element->getDecorator('UiWidgetElement');
        }
        if (strpos($class, 'File')) {
            $dec = $element->getDecorator('File');
        }

        if (!isset($dec) || $dec == false) {
            $dec = $element->getDecorator('ViewHelper');
        }

        return $dec;
    }

    /**
     * Add a display group to the subform
     *
     * This allows to render multiple fields in one table cell. Provide a description to set the label for
     * the group. When the special option showLabels is set to true, inside the tabel cell all fields will
     * still show their own label.
     *
     * Example:
     * <code>
     * $this->addDisplayGroup(array('firstname', 'infix', 'lastname'), 'name_group', array('description'=>'Name', 'showLabels'=>true);
     * </code>
     * This would result in a two cell table row, with first cell the description of the group 'Name' and in the
     * second cell the three input boxes, with their label in front. If you leave the showLabels out, you will get
     * just three inputboxes for the name parts.
     *
     * @param array $elements   Array with names of the fields you want to add
     * @param string $name      The (unique) name for this new group
     * @param array $options    An array of key=>value options to set for the group
     * @return Gems_Form_TabSubForm
     */
    public function addDisplayGroup(array $elements, $name, $options = null)
    {
        //Add the group as usual, but skip decorator loading as we don't need that
        return parent::addDisplayGroup($elements, $name, (array) $options + array('disableLoadDefaultDecorators' => true));
    }

    /**
     * Fix the decorators so we get the table layout we want. Normally this is called
     * only once when rendering the form.
     */
    public function fixDecorators()
    {
        //Needed for alternating rows
        $this->_alternate = new MUtil_Lazy_Alternate(array('odd', 'even'));

        foreach ($this as $name => $element) {
            if ($element instanceof MUtil_Form_Element_Html) {
                $this->_fixDecoratorHtml($element);
                
            } elseif ($element instanceof Zend_Form_Element_Hidden || $element instanceof Zend_Form_Element_Submit) {
                $this->_fixDecoratorHiddenSubmit($element);

            } elseif ($element instanceof Zend_Form_Element) {
                $this->_fixDecoratorElement($element);
                
            } elseif ($element instanceof Zend_Form_DisplayGroup) {
                $this->_fixDecoratorDisplayGroup($element);

            }
        }
    }

    /**
     * Load default decorators
     *
     * @return void
     */
    public function loadDefaultDecorators()
    {
        if ($this->loadDefaultDecoratorsIsDisabled()) {
            return $this;
        }

        $class = $this->getAttrib('class');
        if (empty($class)) {
            $class = 'formTable';
        }

        $decorators = $this->getDecorators();
        if (empty($decorators)) {
            $this->addDecorator('AutoFocus')
                ->addDecorator('FormElements')
                ->addDecorator(array('table' => 'HtmlTag'), array('tag'   => 'table', 'class' => $class))
                ->addDecorator(array('tab' => 'HtmlTag'), array('tag'   => 'div', 'class' => 'displayGroup'))
                ->addDecorator('Form');
        }
        return $this;
    }

    /**
     * Fix the decorators the first time we try to render the form
     *
     * @param Zend_View_Interface $view
     * @return string
     */
    public function render(Zend_View_Interface $view = null)
    {
        if ($this->_getIsRendered()) {
            return;
        }

        $this->fixDecorators();

        return parent::render($view);
    }
}