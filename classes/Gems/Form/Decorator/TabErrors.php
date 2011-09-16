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
     * Recurse through a form object, rendering errors
     *
     * @param  Zend_Form $form
     * @param  Zend_View_Interface $view
     * @return string
     */
    protected function _recurseForm(Zend_Form $form)
    {
        $tabId = 0;
        foreach ($form->getSubForms() as $subitem) {
            if ($subitem instanceof Gems_Form_TabSubForm) {
                //This is where we want to do something
                foreach ($subitem->getElements() as $tabElement) {
                    $messages = $tabElement->getMessages();
                    if (count($messages)) {
                        $subitem->setAttrib('jQueryParams', array('class'=>'taberror'));
                        $errors[$tabId] = $tabId;
                        $form->selectTab($tabId);                        
                        break;
                    }
                }
                $tabId++;
            }
        }
    }
}