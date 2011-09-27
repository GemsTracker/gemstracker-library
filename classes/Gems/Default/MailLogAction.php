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
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: Sample.php 203 2011-07-07 12:51:32Z matijs $
 */

/**
 * Short description for class
 *
 * Long description for class (if any)...
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Default_MailLogAction extends Gems_Controller_ModelSnippetActionAbstract
{
    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Adds a button column to the model, if such a button exists in the model.
     *
     * @param MUtil_Model_TableBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     * @rturn void
     */
    public function addTableColumns(MUtil_Model_TableBridge $bridge, MUtil_Model_ModelAbstract $model)
    {
        if ($menuItem = $this->firstAllowedMenuItem('show')) {
            $bridge->addItemLink($menuItem->toActionLinkLower($this->getRequest(), $bridge));
        }

        // Newline placeholder
        $br = MUtil_Html::create('br');

        $bridge->addMultiSort('grco_created',  $br, 'respondent_name', $br, 'grco_address');
        $bridge->addMultiSort('grco_id_token', $br, 'assigned_by',     $br, 'grco_sender');
        $bridge->addMultiSort('grco_topic');
    }

    /**
     * The automatically filtered result
     */
    public function autofilterAction($resetMvc = true)
    {
        $filter = array('grco_organization' => $this->escort->getCurrentOrganization());

        $this->autofilterParameters['addTableColumns'] = array($this, 'addTableColumns');
        $this->autofilterParameters['extraFilter']     = $filter;
        $this->autofilterParameters['extraSort']       = array('grco_created' => SORT_DESC);

        return parent::autofilterAction($resetMvc);
    }

    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return MUtil_Model_ModelAbstract
     */
    public function createModel($detailed, $action)
    {
        $model = new Gems_Model_JoinModel('maillog', 'gems__log_respondent_communications');

        $model->addLeftTable('gems__respondents', array('grco_id_to' => 'grs_id_user'));
        $model->addLeftTable('gems__staff', array('grco_id_by' => 'gsf_id_user'));
        $model->addLeftTable('gems__mail_templates', array('grco_id_message' => 'gmt_id_message'));

        $model->addColumn(
            "TRIM(CONCAT(COALESCE(CONCAT(grs_last_name, ', '), '-, '), COALESCE(CONCAT(grs_first_name, ' '), ''), COALESCE(grs_surname_prefix, '')))",
            'respondent_name');
        $model->addColumn(
            "CASE WHEN gems__staff.gsf_id_user IS NULL
                THEN '-'
                ELSE
                    CONCAT(
                        COALESCE(gems__staff.gsf_last_name, ''),
                        ', ',
                        COALESCE(gems__staff.gsf_first_name, ''),
                        COALESCE(CONCAT(' ', gems__staff.gsf_surname_prefix), '')
                    )
                END",
            'assigned_by');

        $model->resetOrder();

        $model->set('grco_created',    'label', $this->_('Date sent'));
        $model->set('respondent_name', 'label', $this->_('Receiver'));
        $model->set('grco_address',    'label', $this->_('To address'), 'itemDisplay', 'MUtil_Html_AElement::ifmail');
        $model->set('assigned_by',     'label', $this->_('Sender'));
        $model->set('grco_sender',     'label', $this->_('From address'), 'itemDisplay', 'MUtil_Html_AElement::ifmail');
        $model->set('grco_id_token',   'label', $this->_('Token'));
        $model->set('grco_topic',      'label', $this->_('Subject'));

        if ($detailed) {
            $model->set('gmt_subject', 'label', $this->_('Template'));
        } else {
            $model->set('grco_created', 'formatFunction', $this->util->getTranslated()->formatDate);
        }

        return $model;
    }

    /**
     * Action for showing a browse page
     */
    public function indexAction()
    {
        $this->html->h3($this->_('Mail Activity Log'));

        // MUtil_Echo::track($this->indexParameters);
        parent::indexAction();
    }


    /**
     * Action for showing an item page
     */
    public function showAction()
    {
        $this->html->h3($this->_('Show Mail Activity Log item'));

        // MUtil_Echo::track($this->indexParameters);
        parent::showAction();
    }
}
