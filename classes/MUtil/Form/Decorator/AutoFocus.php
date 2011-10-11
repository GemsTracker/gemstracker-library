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
 * @subpackage Form
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Form decorator that sets the focus on the first suitable element.
 *
 * @package    MUtil
 * @subpackage Form
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class MUtil_Form_Decorator_AutoFocus extends Zend_Form_Decorator_Abstract
{
    private function _getFocus($element)
    {
        // MUtil_Echo::r(get_class($element));
        if ($element instanceof MUtil_Form_Element_SubFocusInterface) {
            foreach ($element->getSubFocusElements() as $subelement) {
                if ($focus = $this->_getFocus($subelement)) {
                    return $focus;
                }
            }
        } elseif ($element instanceof Zend_Form_Element) {
            if (($element instanceof Zend_Form_Element_Hidden) ||
                ($element instanceof MUtil_Form_Element_NoFocusInterface) ||
                ($element->getAttrib('readonly')) ||
                ($element->helper == 'Button') ||
                ($element->helper == 'formSubmit') ||
                ($element->helper == 'SubmitButton')) {
                return null;
            }
            return $element->getId();

        } elseif (($element instanceof Zend_Form) ||
                  ($element instanceof Zend_Form_DisplayGroup)) {
            foreach ($element as $subelement) {
                if ($focus = $this->_getFocus($subelement)) {
                    return $focus;
                }
            }
        }

        return null;
    }

    /**
     * Render form elements
     *
     * @param  string $content
     * @return string
     */
    public function render($content)
    {
        $form  = $this->getElement();
        $view  = $form->getView();
        $focus = $this->_getFocus($form);

        if (($view !== null) && ($focus !== null)) {
            // Use try {} around e.select as nog all elements have a select() function
            $script = "e = document.getElementById('$focus'); if (e) {e.focus(); try { e.select(); } catch (ex) {}}";
            $view->inlineScript()->appendScript($script);
        }

        return $content;
    }
}
