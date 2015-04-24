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
abstract class Gems_Default_RespondentNewAction extends \Gems_Controller_ModelSnippetActionAbstract
{
    /**
     *
     * @var \Gems_AccessLog
     */
    public $accesslog;

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterParameters = array(
        'columns'     => 'getBrowseColumns',
        'extraSort'   => array('gr2o_opened' => SORT_DESC),
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
     * The default search data to use.
     *
     * @var array()
     */
    protected $defaultSearchData = array('grc_success' => 1);

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
     * @var \Gems_Loader
     */
    public $loader;

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
        '-run-once'      => 'openedRespondent',
    );

    /**
     * The snippets used for the show action
     *
     * @var mixed String or array of snippets name
     */
    protected $showSnippets = array(
        'Generic_ContentTitleSnippet',
        'RespondentDetailsSnippet',
    	'Tracker\\AddTracksSnippet',
        'RespondentTokenTabsSnippet',
        'RespondentTokenSnippet',
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
     * @return \MUtil_Model_ModelAbstract
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

        $bridge = $model->getBridgeFor('form', new \Gems_Form());
        $bridge->addSelect('gr2o_reception_code',
            'label', $this->_('Rejection code'),
            'multiOptions', $options,
            'required', true,
            'size', max(7, min(3, count($options) + 1)));

        $form = $bridge->getForm();

        $save = new \Zend_Form_Element_Submit(
                'save_button',
                array('label' => $this->_('Delete respondent'), 'class' => 'button')
                );
        $form->addElement($save);

        if ($request->isPost()) {
            $oldCode = $data['gr2o_reception_code'];
            $data    = $_POST + $data;

            if ($form->isValid($data )) {
                $code = $model->setReceptionCode(
                        $data['gr2o_patient_nr'],
                        $data['gr2o_id_organization'],
                        $data['gr2o_reception_code'],
                        $data['gr2o_id_user'],
                        $oldCode
                        );

                // Is the respondent really removed
                if (! $code->isSuccess()) {

                    // Perform actual save, but not simple stop codes.
                    if ($code->isForRespondents()) {
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
                $this->addMessage($this->_('Input error! No changes saved!'), 'danger');
            }
        }
        $form->populate($data);

        $table = new \MUtil_Html_TableElement(array('class' => 'formTable'));
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
        $br = \MUtil_Html::create('br');

        // Display separator and phone sign only if phone exist.
        $phonesep = \MUtil_Html::raw('&#9743; '); // $bridge->itemIf($bridge->grs_phone_1, \MUtil_Html::raw('&#9743; '));
        $citysep  = \MUtil_Html::raw('&nbsp;&nbsp;'); // $bridge->itemIf($bridge->grs_zipcode, \MUtil_Html::raw('&nbsp;&nbsp;'));

        $filter = $this->getSearchFilter();
        if (isset($filter[\MUtil_Model::REQUEST_ID2])) {
            $column2 = 'gr2o_opened';
        } else {
            $column2 = 'gr2o_id_organization';
        }
        $filter = $this->getSearchFilter();
        if (isset($filter['grc_success']) && (! $filter['grc_success'])) {
            $model->set('grc_description', 'label', $this->_('Rejection code'));
            $column2 = 'grc_description';
        }
        $columns[10] = array('gr2o_patient_nr', $br, $column2);
        $columns[20] = array('name',            $br, 'grs_email');
        $columns[30] = array('grs_address_1',   $br, 'grs_zipcode', $citysep, 'grs_city');
        $columns[40] = array('grs_birthday',    $br, $phonesep, 'grs_phone_1');

        return $columns;
    }

    /**
     * Get the link to edit respondent
     *
     * @return \MUtil_Html_HrefArrayAttribute
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
            \MUtil_Model::REQUEST_ID1 => $this->_getParam(\MUtil_Model::REQUEST_ID1),
            \MUtil_Model::REQUEST_ID2 => $this->_getParam(\MUtil_Model::REQUEST_ID2),
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
        $orgId  = $this->_getParam(\MUtil_Model::REQUEST_ID2);
        $respId = $this->getRespondentId();
        $userId = $this->loader->getCurrentUser()->getUserId();

        // Check for completed tokens
        $this->loader->getTracker()->processCompletedTokens($respId, $userId, $orgId);

        $model = $this->getModel();
        return $model->applyRequest($this->getRequest(), true)->loadFirst();
    }

    /**
     * Retrieve the respondent id
     * (So we don't need to repeat that for every snippet.)
     *
     * @return int
     */
    public function getRespondentId()
    {
        static $respondentId = false;

        if (false !== $respondentId) {
            return $respondentId;
        }

        $orgId        = $this->_getParam(\MUtil_Model::REQUEST_ID2);
        $patientNr    = $this->_getParam(\MUtil_Model::REQUEST_ID1);

        if ($orgId && $patientNr) {
            $respondentId = $this->util->getDbLookup()->getRespondentId($patientNr, $orgId);
        } else {
            $respondentId = null;
        }

        return $respondentId;
    }

    /**
     * Function to allow the creation of search defaults in code
     *
     * @see getSearchFilter()
     *
     * @return array
     */
    public function getSearchDefaults()
    {
        if (! isset($this->defaultSearchData[\MUtil_Model::REQUEST_ID2])) {
            $user = $this->loader->getCurrentUser();

            if ($user->hasPrivilege('pr.respondent.multiorg') && (!$user->getCurrentOrganization()->canHaveRespondents())) {
                $this->defaultSearchData[\MUtil_Model::REQUEST_ID2] = '';
            } else {
                $this->defaultSearchData[\MUtil_Model::REQUEST_ID2] = $user->getCurrentOrganizationId();
            }
        }
        return parent::getSearchDefaults();
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
     * Initialize translate and html objects
     *
     * Called from {@link __construct()} as final step of object instantiation.
     *
     * @return void
     */
    public function init()
    {
        parent::init();

        // Tell the system where to return to after a survey has been taken
        $this->loader->getCurrentUser()->setSurveyReturn($this->getRequest());
    }

    /**
     *
     * @return \Gems_Default_RespondentNewAction
     */
    protected function openedRespondent()
    {
        $orgId     = $this->_getParam(\MUtil_Model::REQUEST_ID2);
        $patientNr = $this->_getParam(\MUtil_Model::REQUEST_ID1);

        if ($patientNr && $orgId) {
            $user = $this->loader->getCurrentUser();

            $where['gr2o_patient_nr = ?']      = $patientNr;
            $where['gr2o_id_organization = ?'] = $orgId;

            $values['gr2o_opened']             = new \MUtil_Db_Expr_CurrentTimestamp();
            $values['gr2o_opened_by']          = $user->getUserId();

            $this->db->update('gems__respondent2org', $values, $where);
        }

        return $this;
    }
}
