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
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: RespondentNewAction.php$
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6
 */
abstract class Gems_Default_RespondentNewAction extends Gems_Controller_ModelSnippetActionAbstract
{
    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterParameters = array(
        'columns'     => 'getBrowseColumns',
        'extraFilter' => array('grc_success' => 1),
        );

    /**
     * The parameters used for the create and edit actions.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $createEditParameters = array('resetRoute' => true, 'useTabbedForm' => true);

    /**
     * The snippets used for the create and edit actions.
     *
     * @var mixed String or array of snippets name
     */
    protected $createEditSnippets = 'RespondentFormSnippet';

    /**
     * The snippets used for the delete action.
     *
     * @var mixed String or array of snippets name
     */
    public $deleteSnippets = array('RespondentDetailsSnippet');

    /**
     * The snippets used for the export action.
     *
     * @var mixed String or array of snippets name
     */
    public $exportSnippets = array('RespondentDetailsSnippet');

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = array('Generic_ContentTitleSnippet', 'RespondentSearchSnippet');

    /**
     *
     * @var Gems_Loader
     */
    public $loader;

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected $showSnippets = array(
        'Generic_ContentTitleSnippet',
        'RespondentDetailsSnippet',
    	'AddTracksSnippet',
        'RespondentTokenTabsSnippet',
        'RespondentTokenSnippet',
    );

    /**
     * The parameters used for the show action
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $showParameters = array(
        'baseUrl'        => 'getItemUrlArray',
        'forOtherOrgs'   => 'getOtherOrgs',
        'onclick'        => 'getEditLink',
        'respondentData' => 'getRespondentData',
    );

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
    protected function createModel($detailed, $action)
    {
        $model = $this->loader->getModels()->createRespondentModel();

        if (! $detailed) {
            return $model->applyBrowseSettings();
        }

        switch ($action) {
            case 'create':
            case 'edit':
            case 'import':
                return $model->applyEditSettings();

            default:
                return $model->applyDetailSettings();
        }
    }

    /**
     * Adjusted delete action
     */
    public function deleteAction()
    {
        $model   = $this->getModel();
        $params  = $this->_processParameters($this->showParameters);
        $data    = $params['respondentData'];
        $request = $this->getRequest();

        $options = $this->util->getReceptionCodeLibrary()->getRespondentDeletionCodes();

        $bridge = new MUtil_Model_FormBridge($model, new Gems_Form());
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

                $code = $this->util->getReceptionCode($data['gr2o_reception_code']);

                // Is the respondent really removed
                if (! $code->isSuccess()) {
                    $userId = $this->loader->getCurrentUser()->getUserId();

                    // Cascade to tracks
                    // the responsiblilty to handle it correctly is on the sub objects now.
                    $tracks = $this->loader->getTracker()->getRespondentTracks($data['gr2o_id_user'], $data['gr2o_id_organization']);
                    foreach ($tracks as $track) {
                        $track->setReceptionCode($code, null, $userId);
                    }

                    // Perform actual save, but not simple stop codes.
                    if ($code->isForRespondents()) {
                        $values['gr2o_reception_code'] = $data['gr2o_reception_code'];
                        $values['gr2o_changed']        = new MUtil_Db_Expr_CurrentTimestamp();
                        $values['gr2o_changed_by']     = $userId;

                        $where = 'gr2o_id_user = ? AND gr2o_id_organization = ?';
                        $where = $this->db->quoteInto($where, $data['gr2o_id_user'], null, 1);
                        $where = $this->db->quoteInto($where, $data['gr2o_id_organization'], null, 1);

                        $this->db->update('gems__respondent2org', $values, $where);

                        $this->addMessage($this->_('Respondent deleted.'));
                        $this->_reroute(array('action' => 'index'), true);
                    } else {
                        // Just a stop code
                        $this->addMessage($this->_('Respondent tracks stopped.'));
                        $this->_reroute(array('action' => 'show'));
                    }
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

        $this->addSnippets($this->deleteSnippets, $params);

        $this->html[] = $form;
    }

    /**
     * Action for dossier export
     */
    public function exportAction()
    {
        $params = $this->_processParameters($this->showParameters);
        $data   = $params['respondentData'];

        $this->addSnippets($this->exportSnippets, $params);

        //Now show the export form
        $export = $this->loader->getRespondentExport($this);
        $form = $export->getForm();
        $this->html->h2($this->_('Export respondent archive'));
        $div = $this->html->div(array('id' => 'mainform'));
        $div[] = $form;

        $request = $this->getRequest();

        $form->populate($request->getParams());

        if ($request->isPost()) {
            $export->render((array) $data['gr2o_patient_nr'], $this->getRequest()->getParam('group'), $this->getRequest()->getParam('format'));
        }
    }

    /**
     * Set column usage to use for the browser.
     *
     * Must be an array of arrays containing the input for TableBridge->setMultisort()
     *
     * @return array or false
     */
    public function getBrowseColumns()
    {
        $model = $this->getModel();

        $model->setIfExists('gr2o_opened', 'tableDisplay', 'small');
        $model->setIfExists('grs_email',   'formatFunction', 'MUtil_Html_AElement::ifmail');

        // Newline placeholder
        $br = MUtil_Html::create('br');

        // Display separator and phone sign only if phone exist.
        $phonesep = MUtil_Html::raw('&#9743; '); // $bridge->itemIf($bridge->grs_phone_1, MUtil_Html::raw('&#9743; '));
        $citysep  = MUtil_Html::raw('&nbsp;&nbsp;'); // $bridge->itemIf($bridge->grs_zipcode, MUtil_Html::raw('&nbsp;&nbsp;'));

        $user = $this->loader->getCurrentUser();
        if ($user->hasPrivilege('pr.respondent.multiorg') && (!$user->getCurrentOrganization()->canHaveRespondents())) {
            $columns[] = array('gr2o_patient_nr', $br, 'gr2o_id_organization');
        } else {
            $model->addFilter(array('gr2o_id_organization' => $user->getCurrentOrganizationId()));
            $columns[] = array('gr2o_patient_nr', $br, 'gr2o_opened');
        }
        $columns[] = array('name',            $br, 'grs_email');
        $columns[] = array('grs_address_1',   $br, 'grs_zipcode', $citysep, 'grs_city');
        $columns[] = array('grs_birthday',    $br, $phonesep, 'grs_phone_1');

        return $columns;
    }

    /**
     * Get the link to edit respondent
     *
     * @return MUtil_Html_HrefArrayAttribute
     */
    public function getEditLink()
    {
        $request = $this->getRequest();

        $item = $this->menu->find(array(
            $request->getControllerKey() => $request->getControllerName(),
            $request->getActionKey() => 'edit',
            'allowed' => true));

        if ($item) {
            return $item->toHRefAttribute($request);
        }
    }

    /**
     * Get the possible translators for the import snippet.
     *
     * @return array of MUtil_Model_ModelTranslatorInterface objects
     */
    public function getImportTranslators()
    {
        $trs = new Gems_Model_Translator_RespondentTranslator($this->_('Simple import'), $this->db);
        $this->applySource($trs);

        return array('default' => $trs);
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return $this->_('Respondents');
    }

    /**
     * Return the array with items that should be used to find this item
     *
     * @return array
     */
    public function getItemUrlArray()
    {
        return array(
            MUtil_Model::REQUEST_ID1 => $this->_getParam(MUtil_Model::REQUEST_ID1),
            MUtil_Model::REQUEST_ID2 => $this->_getParam(MUtil_Model::REQUEST_ID2)
                );
    }

    /**
     * The organisations whose tokens are shown.
     *
     * When true: show tokens for all organisations, false: only current organisation, array => those organisations
     *
     * @return boolean|array
     */
    public function getOtherOrgs()
    {
        // Do not show data from other orgs
        return false;

        // Do show data from all other orgs
        // return true;

        // Return the organisations the user is allowed to see.
        // return array_keys($this->loader->getCurrentUser()->getAllowedOrganizations());
    }

    /**
     * Retrieve the respondent data in advance
     * (So we don't need to repeat that for every snippet.)
     *
     * @return array
     */
    public function getRespondentData()
    {
        $model = $this->getModel();
        $data  = $model->applyRequest($this->getRequest(), true)->loadFirst();

        if (! isset($data['grs_id_user'])) {
            $this->addMessage(sprintf($this->_('Unknown %s requested'), $this->getTopic()));
            $this->_reroute(array('action' => 'index'), true);
            return array();
        }

        // Log
        $this->openedRespondent($data['gr2o_patient_nr'], $data['gr2o_id_organization'], $data['grs_id_user']);

        // Check for completed tokens
        if ($this->loader->getTracker()->processCompletedTokens($data['grs_id_user'], $this->session->user_id, $data['gr2o_id_organization'])) {
            //As data might have changed due to token events... reload
            $data  = $model->applyRequest($this->getRequest(), true)->loadFirst();
        }

        return $data;
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('respondent', 'respondents', $count);;
    }

    /**
     * Overrule default index for the case that the current
     * organization cannot have users.
     */
    public function indexAction()
    {
        $user = $this->loader->getCurrentUser();

        if ($user->hasPrivilege('pr.respondent.multiorg') || $user->getCurrentOrganization()->canHaveRespondents()) {
            parent::indexAction();
        } else {
            $this->addSnippet('Organization_ChooseOrganizationSnippet');
        }
    }

    /**
     * Log the respondent opening
     *
     * @param string $patientId
     * @param int $orgId
     * @param int $userId
     * @return \Gems_Default_RespondentNewAction
     */
    protected function openedRespondent($patientId, $orgId = null, $userId = null)
    {
        if ($patientId) {
            $where['gr2o_patient_nr = ?']      = $patientId;
            $where['gr2o_id_organization = ?'] = $orgId ? $orgId : $this->escort->getCurrentOrganization();
            $values['gr2o_opened']             = new MUtil_Db_Expr_CurrentTimestamp();
            $values['gr2o_opened_by']          = $this->session->user_id;

            $this->db->update('gems__respondent2org', $values, $where);
        }

        return $this;
    }
}
