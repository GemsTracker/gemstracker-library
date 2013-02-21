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

    public function indexAction()
    {
        $export = $this->loader->getRespondentExport($this);
        $form   = $export->getForm();

        $element = new Zend_Form_Element_Textarea('id');
        $element->setLabel($this->_('Respondent numbers'))
                ->setAttrib('cols', 60)
                ->setAttrib('rows', 4)
                ->setOrder(-1)
                ->setDescription($this->_('Separate multiple respondents with a comma (,) or whitespace'));

        $form->addElement($element);

        $this->html->h2($this->_('Export respondent archive'));
        $div = $this->html->div(array('id' => 'mainform'));
        $div[] = $form;

        $request = $this->getRequest();

        $form->populate($request->getParams());

        if ($request->isPost()) {
            $respondents = preg_split('/[\s,;]+/', $request->getParam('id'), -1, PREG_SPLIT_NO_EMPTY);

            $export->render($respondents, $this->getRequest()->getParam('group'), $this->getRequest()->getParam('format'));
        }
    }
}