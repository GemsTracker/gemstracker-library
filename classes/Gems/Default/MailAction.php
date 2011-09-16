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
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 * @package Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.1
 */
class Gems_Default_MailAction extends Gems_Controller_BrowseEditAction
{
    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * $return MUtil_Model_ModelAbstract
     */
    public function createModel($detailed, $action)
    {
        $model = new MUtil_Model_TableModel('gems__mail_templates');
        $model->set('gmt_subject', 'label', $this->_('Subject'));

        if ($detailed) {
            $model->set('gmt_body',
                'label', $this->_('Message'),
                'itemDisplay', array('Gems_Email_EmailFormAbstract', 'displayMailText'));
        }

        return $model;
    }

    public function getAutoSearchElements(MUtil_Model_ModelAbstract $model, array $data)
    {
        $elements = parent::getAutoSearchElements($model, $data);
        $options = array('' => $this->_('(all organizations)')) + $this->util->getDbLookup()->getOrganizations();

        $elements[] = new Zend_Form_Element_Select('org_id', array('multiOptions' => $options));

        return $elements;
    }

    protected function getDataFilter(array $data)
    {
        if (isset($data['org_id']) && $data['org_id']) {
            $organizationId = intval($data['org_id']);
            return array("LOCATE('|$organizationId|', gmt_organizations) > 0");
        }

        return parent::getDataFilter($data);
    }

    public function getTopic($count = 1)
    {
        return $this->plural('email template', 'email templates', $count);
    }

    public function getTopicTitle()
    {
        return $this->_('Email templates');
    }

    protected function processForm($saveLabel = null, $data = null)
    {
        $model = $this->getModel();
        $isNew = ! $model->applyRequest($this->getRequest())->hasFilter();
        $form  = new Gems_Email_MailTemplateForm($this->escort);

        $wasSaved = $form->processRequest($this->_request);

        if ($form->hasMessages()) {
            $this->addMessage($form->getMessages());
        }

        if ($wasSaved) {
            $this->addMessage(sprintf($this->_('%2$u %1$s saved'), $this->getTopic($wasSaved), $wasSaved));
            $this->afterSaveRoute($form->getValues());

        } else {
            $table = new MUtil_Html_TableElement(array('class' => 'formTable'));
            $table->setAsFormLayout($form, true, true);
            $table['tbody'][0][0]->class = 'label';  // Is only one row with formLayout, so all in output fields get class.
            if ($links = $this->createMenuLinks(10)) {
                $table->tf(); // Add empty cell, no label
                $linksCell = $table->tf($links);
            }

            return $form;
        }
    }
}
