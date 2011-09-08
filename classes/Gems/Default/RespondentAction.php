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
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: RespondentAction.php 460 2011-08-31 16:17:26Z mjong $
 */

/**
 * Generic controller class for showing and editing respondents
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
abstract class Gems_Default_RespondentAction extends Gems_Controller_BrowseEditAction implements Gems_Menu_ParameterSourceInterface
{
    public $showSnippets;

    public $filterStandard = array('grc_success' => 1);

    public $sortKey = array('gr2o_opened' => SORT_DESC);

    public $useTabbedForms = true;

    protected function addBrowseTableColumns(MUtil_Model_TableBridge $bridge, MUtil_Model_ModelAbstract $model)
    {
        $model->setIfExists('gr2o_opened', 'tableDisplay', 'small');
        $model->setIfExists('grs_email',   'itemDisplay', 'MUtil_Html_AElement::ifmail');

        if ($menuItem = $this->findAllowedMenuItem('show')) {
            $bridge->addItemLink($menuItem->toActionLinkLower($this->getRequest(), $bridge));
        }

        // Newline placeholder
        $br = MUtil_Html::create('br');

        // Display separator and phone sign only if phone exist.
        $phonesep = $bridge->itemIf($bridge->grs_phone_1, MUtil_Html::raw('&#9743; '));
        $citysep  = $bridge->itemIf($bridge->grs_zipcode, MUtil_Html::raw('&nbsp;&nbsp;'));

        $bridge->addMultiSort('gr2o_patient_nr', $br, 'gr2o_opened');
        $bridge->addMultiSort('name',            $br, 'grs_email');
        $bridge->addMultiSort('grs_address_1',   $br, 'grs_zipcode', $citysep, 'grs_city');
        $bridge->addMultiSort('grs_birthday',    $br, $phonesep, 'grs_phone_1');

        if ($menuItem = $this->findAllowedMenuItem('edit')) {
            $bridge->addItemLink($menuItem->toActionLinkLower($this->getRequest(), $bridge));
        }
    }

    public function afterSave(array $data, $isNew)
    {
        $this->openedRespondent($data['gr2o_patient_nr'], $data['gr2o_id_organization'], $data['grs_id_user']);
        return true;
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
        return $this->loader->getModels()->getRespondentModel($detailed, $action);
    }

    public function deleteAction()
    {
        $model   = $this->getModel();
        $request = $this->getRequest();
        $data    = $model->applyRequest($request, true)->loadFirst();

        if (! isset($data['grs_id_user'])) {
            $this->addMessage(sprintf($this->_('Unknown %s requested'), $this->getTopic()));
            $this->_reroute(array('action' => 'index'));
        }

        // Log
        $this->openedRespondent($data['gr2o_patient_nr'], $data['gr2o_id_organization'], $data['grs_id_user']);

        $sql = 'SELECT grc_id_reception_code, grc_description FROM gems__reception_codes WHERE grc_active = 1 AND grc_for_respondents = 1 ORDER BY grc_description';
        $options = $this->db->fetchPairs($sql);

        $bridge = new MUtil_Model_FormBridge($model, $this->createForm());
        $bridge->addSelect('gr2o_reception_code',
            'label', $this->_('Rejection code'),
            'multiOptions', $options,
            'required', true,
            'size', max(7, min(3, count($options) + 1)));

        $form = $bridge->getForm();

        $save = new Zend_Form_Element_Submit('save_button', array('label' => $this->_('Delete respondent'), 'class' => 'button'));
        $form->addElement($save);

        if ($request->isPost()) {
            $data = $_POST + $data;
            if ($form->isValid($data )) {
                // Is really removed
                if ($data['gr2o_reception_code'] != GemsEscort::RECEPTION_OK) {

                    // Perform actual save
                    $where = 'gr2o_id_user = ? AND gr2o_id_organization = ?';
                    $where = $this->db->quoteInto($where, $data['gr2o_id_user'], null, 1);
                    $where = $this->db->quoteInto($where, $data['gr2o_id_organization'], null, 1);
                    $this->db->update('gems__respondent2org', array(
                        'gr2o_reception_code' => $data['gr2o_reception_code'],
                        'gr2o_changed' => new Zend_Db_Expr('CURRENT_TIMESTAMP'),
                        'gr2o_changed_by' => $this->session->user_id),
                        $where);

                    // Check for redo or overwrite answer in reception code.
                    $sql = 'SELECT grc_overwrite_answers
                                FROM gems__reception_codes
                                WHERE grc_overwrite_answers = 1 AND grc_id_reception_code = ? LIMIT 1';
                    if ($this->db->fetchOne($sql, $data['gr2o_reception_code'])) {
                        // Update consent for tokens
                        $consentCode = $this->util->getConsentRejected();

                        $tracker = $this->loader->getTracker();
                        $tokenSelect = $tracker->getTokenSelect(true);
                        $tokenSelect
                                ->andReceptionCodes()
                                ->andRespondentOrganizations()
                                ->andConsents()
                                ->forRespondent($data['gr2o_id_user'], $data['gr2o_id_organization']);

                        // Update reception code for tokens
                        $tokens  = $tokenSelect->fetchAll();

                        // When a TRACK is removed, all tokens are automatically revoked
                        foreach ($tokens as $tokenData) {
                            $token = $tracker->getToken($tokenData);
                            if ($token->hasSuccesCode() && $token->inSource()) {

                                $token->getSurvey()->updateConsent($token, $consentCode);

                                // TODO: Decide what to do: now we only update the consent codes, not
                                // the token and respondentTrack consent codes
                                // $token->setReceptionCode($data['gr2t_reception_code'], null, $this->session->user_id);
                            }
                        }
                    }
                    $this->addMessage($this->_('Respondent deleted.'));
                    $this->_reroute(array('action' => 'index'), true);
                } else {
                    $this->addMessage($this->_('Choose a reception code to delete.'));
                }
            } else {
                $this->addMessage($this->_('Input error! No changes saved!'));
            }
        }
        $form->populate($data);

        $table = new MUtil_Html_TableElement(array('class' => 'formTable'));
        $table->setAsFormLayout($form, true, true);
        $table['tbody'][0][0]->class = 'label';  // Is only one row with formLayout, so all in output fields get class.

        if ($links = $this->createMenuLinks(10)) {
            $table->tf(); // Add empty cell, no label
            $linksCell = $table->tf($links);
        }

        $params['model']   = $model;
        $params['onclick'] = $this->findAllowedMenuItem('show');
        if ($params['onclick']) {
            $params['onclick'] = $params['onclick']->toHRefAttribute($this->getRequest());
        }
        $params['respondentData'] = $data;
        $this->addSnippet(reset($this->showSnippets), $params);

        $this->html[] = $form;
    }

    public function getMenuParameter($name, $default)
    {
        switch ($name) {
            case 'gr2o_patient_nr':
                return $this->_getParam(MUtil_Model::REQUEST_ID, $default);

            case 'gto_id_token':
                return null;

            default:
                return $this->_getParam($name, $default);
        }
    }

    public function getTopic($count = 1)
    {
        return $this->plural('respondent', 'respondents', $count);
    }

    public function getTopicTitle()
    {
        return $this->_('Respondents');
    }

    protected function openedRespondent($patientId, $orgId = null, $userId = null)
    {
        if ($patientId) {
            $where['gr2o_patient_nr = ?']      = $patientId;
            $where['gr2o_id_organization = ?'] = $orgId ? $orgId : $this->escort->getCurrentOrganization();
            $values['gr2o_opened']    = new Zend_Db_Expr('CURRENT_TIMESTAMP');
            $values['gr2o_opened_by'] = $this->session->user_id;

            $this->db->update('gems__respondent2org', $values, $where);
        }

        return $this;
    }

    public function showAction()
    {
        $model = $this->getModel();
        $data  = $model->applyRequest($this->getRequest(), true)->loadFirst();

        if (! isset($data['grs_id_user'])) {
            $this->addMessage(sprintf($this->_('Unknown %s requested'), $this->getTopic()));
            $this->_reroute(array('action' => 'index'));
        }

        // Log
        $this->openedRespondent($data['gr2o_patient_nr'], $data['gr2o_id_organization'], $data['grs_id_user']);

        // Check for completed tokens
        $this->loader->getTracker()->processCompletedTokens($data['grs_id_user'], $this->session->user_id);

        if ($data['gr2o_consent'] == $model->get('gr2o_consent', 'default')) {
            $url = $this->view->url(array('controller' => 'respondent', 'action' => 'edit', 'id' => $data['gr2o_patient_nr'])) . '#tabContainer-frag-3';
            $this->addMessage(MUtil_Html::create()->a($url, $this->_('Please settle the informed consent form for this respondent.')));
        }

        $params['model']   = $model;
        $params['baseUrl'] = array(MUtil_Model::REQUEST_ID => $this->_getParam(MUtil_Model::REQUEST_ID));
        $params['buttons'] = $this->createMenuLinks();
        $params['onclick'] = $this->findAllowedMenuItem('edit');
        if ($params['onclick']) {
            $params['onclick'] = $params['onclick']->toHRefAttribute($this->getRequest());
        }
        $params['respondentData'] = $data;
        $this->addSnippets($this->showSnippets, $params);
    }
}