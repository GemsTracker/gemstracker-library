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
 * @package    Gems
 * @subpackage Form
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Takes care of rendering errors in tabbed forms
 *
 * $Id$
 * @filesource
 * @package Gems
 * @subpackage Form
 */
class Gems_Form_Decorator_TabErrors extends Zend_Form_Decorator_Abstract
{
    /**
     * By default, show verbose error messages in tabforms
     * 
     * @var boolean
     */
    protected $_verbose = true;

    /**
     * Render the TabErrors
     *
     * We don't return anything, we just add a class to the tab so it shows the errors
     *
     * @param <type> $content
     * @return <type>
     */
    public function render($content) {
        $form = $this->getElement();
        if (!$form instanceof Zend_Form) {
            return $content;
        }

        $this->_recurseForm($form);

        return $content;
    }

    /**
     * Should the tab errors be verbose?
     * 
     * Verbose means that apart from marking and selecting the tab that has errors
     * we also show an error above the form.
     * 
     * @return boolean
     */
    public function getVerbose() {
        if (null !== ($verboseOpt = $this->getOption('verbose'))) {
            $this->_verbose = (bool) $verboseOpt;
            $this->removeOption('verbose');
        }

        return $this->_verbose;
    }

    /**
     * Recurse through a form object, rendering errors
     *
     * @param  Zend_Form $form
     * @param  Zend_View_Interface $view
     * @return string
     */
    protected function _recurseForm(Zend_Form $form)
    {
        $subFormsWithErrors = array();
        $subFormMessages = array();
        $tabId = 0;
        
        foreach ($form->getSubForms() as $subForm) {
            if ($subForm instanceof Gems_Form_TabSubForm) {
                // See if any of the subformelements has an error message
                foreach ($subForm->getElements() as $subFormElement) {
                    $elementMessages = $subFormElement->getMessages();
                    if (count($elementMessages)) {
                        $subFormsWithErrors[$tabId] = $subForm->getAttrib('title'); // Save subform title
                        $subForm->setAttrib('jQueryParams', array('class'=>'taberror'));    // Add css class to the subform
                        $form->selectTab($tabId);   // Select the tab, this way the last tab with error is always selected
                        break;  // don't check other elements
                        
                    }
                }

                // Preserve subform level custom messages if we have an error
                if (array_key_exists($tabId, $subFormsWithErrors)) {
                     $subFormMessages[$tabId] = $subForm->getCustomMessages();
                }
                $tabId++;
            }

            // If we found at least one error, and 'verbose' is true
            if ($this->getVerbose() && (!empty($subFormsWithErrors) || $form->isErrors()) )  {
                // First show form level custom error messages (the elements show their own errors)
                $formMessage = $form->getCustomMessages();
                if(!empty($formMessage)) {
                    foreach($formMessage as $message)
                    {
                        Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger')->addMessage($message);
                    }
                }

                // Now browse through the tabs with errors
                foreach ($subFormsWithErrors as $tabIdx => $tabName)
                {
                    // If more then one tab, show in which tab we found the errors
                    if ($tabId > 1) {
                        $translator = Zend_Registry::get('Zend_Translate');
                        Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger')->addMessage(sprintf($translator->_('Error in tab "%s"'), $tabName));
                    }

                    // If we have them, show the tab custom error messages
                    foreach ($subFormMessages[$tabIdx] as $subFormMessage)
                    {                        
                        foreach ($subFormMessage as $message)
                        {
                            Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger')->addMessage("--> " . $message);
                        }                        
                    }
                }

            }
        }
    }
}