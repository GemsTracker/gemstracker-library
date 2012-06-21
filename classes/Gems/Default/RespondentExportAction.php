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
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Standard controller to export respondent data to html
 *
 * @package    Gems
 * @subpackage Default
 * @author     Michiel Rook <michiel@touchdownconsulting.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */
class Gems_Default_RespondentExportAction extends Gems_Controller_Action
{
    public $useHtmlView = true; 

    /**
     * Constructs the form 
     * 
     * @param Gems_Export_RespondentExport $export
     * @return Gems_Form_TableForm
     */    
    protected function _getForm($export)
    {
        $form = new Gems_Form_TableForm();
        $form->setAttrib('target', '_blank');
        
        $element = new Zend_Form_Element_Text('id');
        $element->setLabel($this->_('Respondent number'));
        
        // only show description if we got here directly (id is empty)
        if ($this->getRequest()->getParam('id') == '') {
            $element->setDescription($this->_('Separate multiple respondents with a comma (,)'));
        }
        
        $form->addElement($element);
        
        $element = new Zend_Form_Element_Checkbox('group');
        $element->setLabel($this->_('Group surveys'));
        $element->setValue(1);
        $form->addElement($element);
        
        $element = new Zend_Form_Element_Select('format');
        $element->setLabel($this->_('Output format'));
        $outputFormats = array('html' => 'HTML');
        if (!empty($export->_wkhtmltopdfLocation)) {
            $outputFormats['pdf'] = 'PDF';
            $element->setValue('pdf');
        }
        $element->setMultiOptions($outputFormats);
        $form->addElement($element);
        
        $element = new Zend_Form_Element_Submit('export');
        $element->setLabel($this->_('Export'))
                ->setAttrib('class', 'button');
        $form->addElement($element);
        
        return $form;
    }
    
    public function indexAction()
    {
        $export = $this->loader->getRespondentExport($this);
        
        $form = $this->_getForm($export);
        $this->html->h2($this->_('Export respondent'));
        $div = $this->html->div(array('id' => 'mainform'));
        $div[] = $form;
        
        $request = $this->getRequest();
        
        $form->populate($request->getParams());
        
        if ($request->isPost()) {
            $respondents = explode(',', $request->getParam('id'));
            $respondents = array_map('trim', $respondents);

            $export->render($respondents, $this->getRequest()->getParam('group'), $this->getRequest()->getParam('format'));
        }
    }
}