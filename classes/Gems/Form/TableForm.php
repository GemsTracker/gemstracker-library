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
 * @subpackage
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
class Gems_Form_TableForm extends Gems_Form {
    /**
     * Whether or not form elements are members of an array
     * @var bool
     */
    protected $_isArray = false;

    private $_alternate = null;

    public function  __construct($options = null) {
        //Needed for alternating rows
        $this->_alternate = new MUtil_Lazy_Alternate(array('odd','even'));
        parent::__construct($options);
    }

    /**
     * Get a ViewHelper or ZendX decorator to add in front of the decorator chain
     *
     * @param array $decorators
     * @return null|Zend_Form_Decorator_Abstract
     */
    private function _getImportantDecorator($decorators) {
        $dec1 = null;

        if (isset($decorators['Zend_Form_Decorator_ViewHelper'])) {
            $dec1 = $decorators['Zend_Form_Decorator_ViewHelper'];
        } elseif (isset($decorators['Zend_Form_Decorator_File'])) {
            $dec1 = $decorators['Zend_Form_Decorator_File'];
        } else {
            foreach($decorators as $name=>$decorator) {
                if (substr($name, 0, 5) == 'ZendX') {
                    $dec1 = $decorator;
                    break;
                }
            }
        }
        return $dec1;
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
        //Add the group as usual
        parent::addDisplayGroup($elements, $name, $options);

        //Fix alternation when number of elements is not even
        if (count($elements) % 2) {
            $dropMe = $this->_alternate->__toString();
        }

        //Retrieve it and set decorators
        $group = $this->getDisplayGroup($name);
        $group->setDecorators( array('FormElements',
                            array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element')),
                            array(array('labelCellClose' => 'HtmlTag'), array('tag' => 'td', 'placement'=>  Zend_Form_Decorator_Abstract::PREPEND, 'closeOnly'=>true)),
                            array('Tooltip'),
                            array('Description', array('tag'=>'label', 'class'=>'optional', 'placement'=>  Zend_Form_Decorator_Abstract::PREPEND, 'escape'=>false)),
                            array(array('labelCellOpen' => 'HtmlTag'), array('tag' => 'td', 'class'=>'label', 'placement'=>  Zend_Form_Decorator_Abstract::PREPEND, 'openOnly'=>true)),
                            array(array('row' => 'HtmlTag'), array('tag' => 'tr', 'class' => $this->_alternate . ' ' . $group->getName(). ' ' . $group->getAttrib('class')))
                            ));

        //Now add the right decorators to the elements
        $groupElements = $group->getElements();
        foreach ($groupElements as $element) {
            $decorators = $element->getDecorators();
            $dec1 = $this->_getImportantDecorator($decorators);

            $decorators = array(    array('Description', array('class'=>'description')),
                                    'Errors',
                                    array('Tooltip'),
                                    );

            //If we want to see the individual fields labels, do so:
            if ($group->getAttrib('showLabels')===true) {
                $decorators[] = array('Label', array('escape'=>false));
            }

            //Apply final class and id to allow for custom styling
            $decorators[] = array(array('labelCell' => 'HtmlTag'), array('tag' => 'div', 'class'=>'tab-displaygroup', 'id'=>$element->getName().'_cont'));

            if (!is_null($dec1)) array_unshift($decorators, $dec1);
            $element->setDecorators($decorators);
            if ($element instanceof Zend_Form_Element_Checkbox) {
                $decorator = $element->getDecorator('Label');
                $decorator->setOption('placement', Zend_Form_Decorator_Label::APPEND);
            }
        }
        return $this;
    }

    /**
     * Add element to stack
     *
     * @param  Zend_Form_Element $element
     * @return Zend_Form_Element
     */
    public function addElement($element, $name = null, $options = null)
    {
        parent::addElement($element, $name, $options);

        if (null === $name) {
            $name = $element->getName();
        } else {
            $element = $this->getElement($name);
        }

        $decorators = $element->getDecorators();
        $dec1 = $this->_getImportantDecorator($decorators);

        if ($element instanceof MUtil_Form_Element_Html) {
            //Colspan 2
            $decorators = array(
            array('Description', array('class'=>'description')),
            'Errors',
            array('Tooltip'),
            array('Label', array('escape'=>false)),
            array(array('labelCell' => 'HtmlTag'), array('tag' => 'td', 'class'=>'label', 'colspan'=>2)),
            array(array('row' => 'HtmlTag'), array('tag' => 'tr', 'class' => $element->getName()))
            );
        } elseif ($element instanceof Zend_Form_Element_Hidden ||
            $element instanceof Zend_Form_Element_Submit) {
            //No label and tooltip
            $decorators = array(
            array('Description', array('class'=>'description')),
            'Errors',
            array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element')),
            array(array('labelCellClose' => 'HtmlTag'), array('tag' => 'td', 'placement'=>  Zend_Form_Decorator_Abstract::PREPEND, 'closeOnly'=>true)),
            array('Tooltip'),
            array(array('labelCellOpen' => 'HtmlTag'), array('tag' => 'td', 'class'=>'label', 'placement'=>  Zend_Form_Decorator_Abstract::PREPEND, 'openOnly'=>true)),
            array(array('row' => 'HtmlTag'), array('tag' => 'tr', 'class' => $element->getName()))
            );
        } else {
            $decorators = array(
            array('Description', array('class'=>'description')),
            'Errors',
            array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element')),
            array(array('labelCellClose' => 'HtmlTag'), array('tag' => 'td', 'placement'=>  Zend_Form_Decorator_Abstract::PREPEND, 'closeOnly'=>true)),
            array('Tooltip'),
            array('Label', array('escape'=>false)),
            array(array('labelCellOpen' => 'HtmlTag'), array('tag' => 'td', 'class'=>'label', 'placement'=>  Zend_Form_Decorator_Abstract::PREPEND, 'openOnly'=>true)),
            array(array('row' => 'HtmlTag'), array('tag' => 'tr', 'class' => $element->getName()))
            );
        }

        if (!is_null($dec1)) array_unshift($decorators, $dec1);
        $element->setDecorators($decorators);

        if ($element instanceof Zend_Form_Element_Hidden) {
            //Add 1000 to the order to place them last and fix some layout problems
            $order = $element->getOrder();
            $element->setOrder($order+1000);

        } else {
            $decorator = $element->getDecorator('row');
            $decorator->setOption('class', $this->_alternate . ' ' . $element->getName());
        }

        return parent::addElement($element);
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

        $decorators = $this->getDecorators();
        if (empty($decorators)) {
            $this->addDecorator('FormElements')
                 ->addDecorator(array('table' => 'HtmlTag'), array('tag' => 'table', 'class'=>'formTable'))
                 ->addDecorator(array('tab' => 'HtmlTag'), array('tag' => 'div', 'class' => 'displayGroup'))
                 ->addDecorator('Form');
        }
        return $this;
    }
}
