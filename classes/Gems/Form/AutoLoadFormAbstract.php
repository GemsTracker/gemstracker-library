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
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Form
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Adds default element loading to standard form
 *
 * @package    Gems
 * @subpackage Form
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.3
 */
abstract class Gems_Form_AutoLoadFormAbstract extends Gems_Form
{
    /**
     * The field name for the submit element.
     *
     * @var string
     */
    protected $_submitFieldName = 'button';

    /**
     * When true all elements are loaded after initiation.
     *
     * @var boolean
     */
    protected $loadDefault = true;

    /**
     *
     * @var Zend_Translate
     */
    protected $translate;

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        if ($this->loadDefault) {
            $this->loadDefaultElements();
        }
    }

    /**
     * When true all elements are loaded after initiation.
     *
     * @return boolean $loadDefault
     */
    public function getLoadDefault($loadDefault = true)
    {
        return $this->loadDefault;
    }

    /**
     * Returns/sets a submit button.
     *
     * @return Zend_Form_Element_Submit
     */
    public function getSubmitButton()
    {
        $element = $this->getElement($this->_submitFieldName);

        if (! $element) {
            // Submit knop
            $options = array('class' => 'button');

            $element = $this->createElement('submit', $this->_submitFieldName, $options);
            $element->setLabel($this->getSubmitButtonLabel());

            $this->addElement($element);
        }

        return $element;
    }

    /**
     * Returns the label for the submitbutton
     *
     * @return string
     */
    abstract public function getSubmitButtonLabel();

    /**
     * The function loads the elements for this form
     *
     * @return Gems_Form_AutoLoadFormAbstract (continuation pattern)
     */
    abstract public function loadDefaultElements();

    /**
     * When true all elements are loaded after initiation.
     *
     * Enables loading of parameter through Zend_Form::__construct()
     *
     * @param boolean $loadDefault
     * @return Gems_User_Form_LoginForm (continuation pattern)
     */
    public function setLoadDefault($loadDefault = true)
    {
        $this->loadDefault = $loadDefault;

        return $this;
    }
}
