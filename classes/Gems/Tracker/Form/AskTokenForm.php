<?php

/**
 * Copyright (c) 2012, Erasmus MC
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
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_Tracker_Form_AskTokenForm extends Gems_Form_AutoLoadFormAbstract
{
    /**
     * The field name for the token element.
     *
     * @var string
     */
    protected $_tokenFieldName = MUtil_Model::REQUEST_ID;

    /**
     *
     * @var Gems_Tracker
     */
    protected $tracker;

    /**
     * Returns/sets a password element.
     *
     * @return Zend_Form_Element_Password
     */
    public function getTokenElement()
    {
        $element = $this->getElement($this->_tokenFieldName);

        if (! $element) {
            $tokenLib   = $this->tracker->getTokenLibrary();
            $max_length = $tokenLib->getLength();

            // Veld token
            $element = new Zend_Form_Element_Text($this->_tokenFieldName);
            $element->setLabel($this->translate->_('Token'));
            $element->setDescription(sprintf($this->translate->_('Enter tokens as %s.'), $tokenLib->getFormat()));
            $element->setAttrib('size', $max_length + 2);
            $element->setAttrib('maxlength', $max_length);
            $element->setRequired(true);
            $element->addFilter($this->tracker->getTokenFilter());
            $element->addValidator($this->tracker->getTokenValidator());

            $this->addElement($element);
        }

        return $element;
    }

    /**
     * Returns the label for the submitbutton
     *
     * @return string
     */
    public function getSubmitButtonLabel()
    {
        return $this->translate->_('OK');
    }

    /**
     * The function loads the elements for this form
     *
     * @return Gems_Form_AutoLoadFormAbstract (continuation pattern)
     */
    public function loadDefaultElements()
    {
        $this->getTokenElement();
        $this->getSubmitButton();

        return $this;
    }

}
