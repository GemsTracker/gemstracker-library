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
 * @version    $Id$
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
    public $showSnippets = array(
        'Respondent_MultiOrganizationTab',
        'RespondentDetailsSnippet',
    	'AddTracksSnippet',
        'RespondentTokenTabsSnippet',
        'RespondentTokenSnippet',
    );

    public $exportSnippets = array('RespondentDetailsSnippet');

    public $filterStandard = array('grc_success' => 1);

    public $sortKey = array('gr2o_opened' => SORT_DESC);

    public $useTabbedForms = true;

    /**
     * Constructs the form
     *
     * @param Gems_Export_RespondentExport $export
     * @return Gems_Form_TableForm
     */
    protected function _getExportForm($export)
    {
        $form = new Gems_Form_TableForm();
        $form->setAttrib('target', '_blank');

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

    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Adds a button column to the model, if such a button exists in the model.
     *
     * @param MUtil_Model_TableBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     * @rturn void
     */
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

        if ($this->loader->getCurrentUser()->hasPrivilege('pr.respondent.multiorg')) {
            $bridge->addMultiSort('gr2o_patient_nr', $br, 'gor_name'); //, MUtil_Html::raw(' '), 'gr2o_opened');
        } else {
            $bridge->addMultiSort('gr2o_patient_nr', $br, 'gr2o_opened');
        }
        $bridge->addMultiSort('name',            $br, 'grs_email');
        $bridge->addMultiSort('grs_address_1',   $br, 'grs_zipcode', $citysep, 'grs_city');
        $bridge->addMultiSort('grs_birthday',    $br, $phonesep, 'grs_phone_1');

        if ($menuItem = $this->findAllowedMenuItem('edit')) {
            $bridge->addItemLink($menuItem->toActionLinkLower($this->getRequest(), $bridge));
        }
    }

    /**
     * Adds elements from the model to the bridge that creates the form.
     *
     * Overrule this function to add different elements to the browse table, without
     * having to recode the core table building code.
     *
     * @param MUtil_Model_FormBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     * @param array $data The data that will later be loaded into the form
     * @param optional boolean $new Form should be for a new element
     * @return void|array When an array of new values is return, these are used to update the $data array in the calling function
     */
    protected function addFormElements(MUtil_Model_FormBridge $bridge, MUtil_Model_ModelAbstract $model, array $data, $new = false)
    {
        if (APPLICATION_ENV !== 'production') {
            $bsn = new MUtil_Validate_Dutch_Burgerservicenummer();
            $num = mt_rand(100000000, 999999999);

            while (! $bsn->isValid($num)) {
                $num++;
            }

            $model->set('grs_ssn', 'description', sprintf($this->_('Random Example BSN: %s'), $num));
        } else {
            $model->set('grs_ssn', 'description', $this->_('Enter a 9-digit SSN number.'));
        }

        $ucfirst = new Zend_Filter_Callback('ucfirst');

        // MUtil_Echo::track($data);

        $bridge->addTab(    'caption1')->h4($this->_('Identification'));
        //Add the hidden fields after the tab, so validation will work. They need to be in the
        //same tab where they are needed
        $bridge->addHidden(  'grs_id_user');
        $bridge->addHidden(  'gr2o_id_organization');
        $bridge->addHidden(   $model->getKeyCopyName('gr2o_patient_nr'));
        $bridge->addHidden(   $model->getKeyCopyName('gr2o_id_organization'));
        if (isset($data['gul_id_user'])) {
            $bridge->addHidden('gul_id_user');
        }

        $bridge->addText(    'grs_ssn',            'label', $this->_('SSN'), 'size', 10, 'maxlength', 12)
            ->addValidator(  new MUtil_Validate_Dutch_Burgerservicenummer())
            ->addValidator(  $model->createUniqueValidator('grs_ssn'))
            ->addFilter(     'Digits');
        $bridge->addText(    'gr2o_patient_nr',    'label', $this->_('Patient number'), 'size', 15, 'minlength', 4)
            ->addValidator(  $model->createUniqueValidator(array('gr2o_patient_nr', 'gr2o_id_organization'), array('gr2o_id_user' => 'grs_id_user', 'gr2o_id_organization')));

        $bridge->addText(    'grs_first_name')
            ->addFilter(     $ucfirst);
        $bridge->addText(    'grs_surname_prefix', 'description', 'de, van der, \'t, etc...');
        $bridge->addText(    'grs_last_name',      'required', true)
            ->addFilter(     $ucfirst);

        $bridge->addTab(    'caption2')->h4($this->_('Medical data'));
        $bridge->addRadio(   'grs_gender',         'separator', '', 'multiOptions', $this->util->getTranslated()->getGenders());
        $year = intval(date('Y')); // Als jQuery 1.4 gebruikt wordt: yearRange = c-130:c0
        $bridge->addDate(    'grs_birthday',       'jQueryParams', array('defaultDate' => '-30y', 'maxDate' => 0, 'yearRange' => ($year - 130) . ':' . $year))
            ->addValidator(new MUtil_Validate_Date_DateBefore());

        //$bridge->addSelect(  'gr2o_id_physician');
        $bridge->addText(    'gr2o_treatment',     'size', 30, 'description', $this->_('DBC\'s, etc...'));
        $bridge->addTextarea('gr2o_comments',      'rows', 4, 'cols', 60);

        $bridge->addTab(    'caption3')->h4($this->_('Contact information'));
        // Setting e-mail to required is niet mogelijk, grijpt te diep in
        // misschien later proberen met ->addGroup('required', 'true'); ???
        $bridge->addText(    'grs_email',          'size', 30) // , 'required', true, 'AutoInsertNotEmptyValidator', false)
            ->addValidator(  'SimpleEmail');
        $bridge->addCheckBox('calc_email',         'label', $this->_('Respondent has no e-mail'));
        $bridge->addText(    'grs_address_1',      'size',  40, 'description', $this->_('With housenumber'))
            ->addFilter(     $ucfirst);
        $bridge->addText(    'grs_address_2',      'size', 40);
        $bridge->addText(    'grs_zipcode',        'size', 7, 'description', '0000 AA');
        $bridge->addFilter(  'grs_zipcode',        new Gems_Filter_DutchZipcode());
        $bridge->addText(    'grs_city')
            ->addFilter(     $ucfirst);
        $bridge->addSelect(  'grs_iso_country',    'label', $this->_('Country'), 'multiOptions', $this->util->getLocalized()->getCountries());
        $bridge->addText(    'grs_phone_1',        'size', 15)
            ->addValidator(  'Phone');

        $bridge->addTab(    'caption4')->h4($this->_('Settings'));
        $bridge->addSelect(  'grs_iso_lang',       'label', $this->_('Language'), 'multiOptions', $this->util->getLocalized()->getLanguages());
        $bridge->addRadio(   'gr2o_consent',       'separator', '', 'description',  $this->_('Has the respondent signed the informed consent letter?'));
    }

    public function afterSave(array $data, $isNew)
    {
        if ($isNew) {
            // Tell the organization it has at least one user
            $org = $this->loader->getOrganization($data['gr2o_id_organization']);
            $org->setHasRespondents($data['grs_id_user']);
        }
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
        $model = $this->loader->getModels()->getRespondentModel($detailed);

        if ($detailed) {
        	$model->set('gr2o_comments',     'label', $this->_('Comments'));
        	$model->set('gr2o_treatment',    'label', $this->_('Treatment'));

            $model->addColumn('CASE WHEN grs_email IS NULL OR LENGTH(TRIM(grs_email)) = 0 THEN 1 ELSE 0 END', 'calc_email');
        }

        $model->set('gr2o_id_organization', 'default', $this->getOrganizationId());

        return $model;
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

        $options = $this->util->getReceptionCodeLibrary()->getRespondentDeletionCodes();

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
                        $values['gr2o_changed']        = new Zend_Db_Expr('CURRENT_TIMESTAMP');
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

    /**
     * Returns a text element for autosearch. Can be overruled.
     *
     * The form / html elements to search on. Elements can be grouped by inserting null's between them.
     * That creates a distinct group of elements
     *
     * @param MUtil_Model_ModelAbstract $model
     * @param array $data The $form field values (can be usefull, but no need to set them)
     * @return array Of Zend_Form_Element's or static tekst to add to the html or null for group breaks.
     */
    protected function getAutoSearchElements(MUtil_Model_ModelAbstract $model, array $data)
    {
        $elements = parent::getAutoSearchElements($model, $data);

        if ($model->isMultiOrganization()) {
            $options = $this->loader->getCurrentUser()->getRespondentOrganizations();

            $elements[] = $this->_createSelectElement(MUtil_Model::REQUEST_ID2, $options, $this->_('(all organizations)'));
        }

        return $elements;
    }

    /**
     * Returns the currently used organization
     *
     * @param int $default Optional default value
     * @return int An organization id
     */
    protected function getOrganizationId($default = null)
    {
        if ($orgId = $this->_getParam(MUtil_Model::REQUEST_ID2, $default)) {
            return $orgId;
        }
        $data = $this->getCachedRequestData(false);
        if (isset($data[MUtil_Model::REQUEST_ID2])) {
            return $data[MUtil_Model::REQUEST_ID2];
        }

        return $this->loader->getCurrentUser()->getCurrentOrganizationId();
    }

    public function getMenuParameter($name, $default)
    {
        switch ($name) {
            case 'gr2o_patient_nr':
                return $this->_getParam(MUtil_Model::REQUEST_ID1, $default);

            case 'gr2o_id_organization':
                return $this->getOrganizationId($default);

            case 'gto_id_token':
                return null;

            default:
                return $this->_getParam($name, $default);
        }
    }

    public function getSubject($data)
    {
        return sprintf('%s - %s', $data['name'], $data['gr2o_patient_nr']);
    }

    public function getTopic($count = 1)
    {
        return $this->plural('respondent', 'respondents', $count);
    }

    public function getTopicTitle()
    {
        return $this->_('Respondents');
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
        if ($this->loader->getTracker()->processCompletedTokens($data['grs_id_user'], $this->session->user_id)) {
            //As data might have changed due to token events... reload
            $data  = $model->applyRequest($this->getRequest(), true)->loadFirst();
        }

        //Check if we have the 'Unknown' consent, and present a warning. The project default consent is
        //normally 'Unknown' but this can be overruled in project.ini so checking for default is not right
        if ($data['gr2o_consent'] == 'Unknown') {
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

    public function exportAction()
    {
        //First show the respondent snippet
        $model = $this->getModel();
        $data  = $model->applyRequest($this->getRequest(), true)->loadFirst();

        if (! isset($data['grs_id_user'])) {
            $this->addMessage(sprintf($this->_('Unknown %s requested'), $this->getTopic()));
            $this->_reroute(array('action' => 'index'));
        }

        $params['model']   = $model;
        $params['baseUrl'] = array(MUtil_Model::REQUEST_ID1 => $this->_getParam(MUtil_Model::REQUEST_ID1), MUtil_Model::REQUEST_ID2 => $this->_getParam(MUtil_Model::REQUEST_ID2));
        $params['buttons'] = $this->createMenuLinks();
        $params['onclick'] = $this->findAllowedMenuItem('edit');
        if ($params['onclick']) {
            $params['onclick'] = $params['onclick']->toHRefAttribute($this->getRequest());
        }
        $params['respondentData'] = $data;
        $this->addSnippets($this->exportSnippets, $params);

        //Now show the export form
        $export = $this->loader->getRespondentExport($this);
        $form = $this->_getExportForm($export);
        $this->html->h2($this->_('Export respondent'));
        $div = $this->html->div(array('id' => 'mainform'));
        $div[] = $form;

        $request = $this->getRequest();

        $form->populate($request->getParams());

        if ($request->isPost()) {
            $export->render((array) $data['gr2o_patient_nr'], $this->getRequest()->getParam('group'), $this->getRequest()->getParam('format'));
        }
    }
}
