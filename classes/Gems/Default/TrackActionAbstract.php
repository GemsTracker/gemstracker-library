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
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
abstract class Gems_Default_TrackActionAbstract extends Gems_Controller_BrowseEditAction
{
    /**
     * Use these snippets to show the content of a track
     *
     * @var mixed can be empty;
     */
    public $addTrackContentSnippets = null;

    protected $availableSort = array('gtr_track_name' => SORT_ASC);

    /**
     *
     * @var Zend_Db_Adaptor_Abstract
     */
    public $db;

    /**
     *
     * @var Gems_Menu
     */
    public $menu;

    /**
     * Storage for respondents name
     *
     * @var string
     */
    protected $respondentName;

    public $summarizedActions = array('index', 'autofilter', 'create');

    // Set when this controller should only showe results from a single tracktype.
    public $trackType = null;

    public $view;

    protected function _createTable()
    {
        $result[] = parent::_createTable();

        if ($this->trackType) {
            $orgId   = trim($this->db->quote($this->_getParam(MUtil_Model::REQUEST_ID2)), "'");
            $model   = $this->createTrackModel(false, 'index');
            $request = $this->getRequest();

            $searchText = $this->_getParam($model->getTextFilter());

            $model->applyPostRequest($request);
            // $model->trackUsage(); DO NOT USE AS all is label here.

            if ($searchText) {
                $marker = new MUtil_Html_Marker($model->getTextSearches($searchText), 'strong', 'UTF-8');
                foreach ($model->getItemNames() as $name) {
                    if ($model->get($name, 'label')) {
                        $model->set($name, 'markCallback', array($marker, 'mark'));
                    }
                }
            }

            $filter     = $model->getTextSearchFilter($searchText);
            $filter['gtr_track_type'] = $this->trackType;
            $filter['gtr_active']     = 1;
            $filter[]   = '(gtr_date_until IS NULL OR gtr_date_until >= CURRENT_DATE) AND gtr_date_start <= CURRENT_DATE';
            $filter[]   = "gtr_organizations LIKE '%|$orgId|%'";

            $menuParams = array(
                'gr2o_patient_nr'        => $this->_getParam(MUtil_Model::REQUEST_ID1),
                'gr2o_id_organization'   => $orgId);
            $baseUrl    = $menuParams + array(
                'action'                 => 'index',
                MUtil_Model::TEXT_FILTER => $searchText);

            $bridge     = new MUtil_Model_TableBridge($model, array('class' => 'browser'));
            $bridge->setBaseUrl($baseUrl);
            $bridge->setOnEmpty($this->_('No tracks found'));
            $bridge->getOnEmpty()->class = 'centerAlign';
            $bridge->setRepeater($model->loadRepeatable($filter, $this->availableSort));

            // Add view button
            if ($menuItem = $this->findAllowedMenuItem('view')) {
                $bridge->addItemLink($menuItem->toActionLinkLower($request, $bridge, $menuParams));
            }

            foreach($model->getItemsOrdered() as $name) {
                if ($label = $model->get($name, 'label')) {
                    $bridge->add($name, $label);
                }
            }

            // Add create button
            if ($menuItem = $this->findAllowedMenuItem('create')) {
                $bridge->addItemLink($menuItem->toActionLinkLower($request, $bridge, $menuParams));
            }

            $result[] = MUtil_Html::create()->h3($this->_('Available tracks'));
            $result[] = $bridge->getTable();
        }

        return $result;
    }

    /**
     * Gets the patient number and organization id from the request (when possible)
     *
     * Typical use:
     * <code>
     *  list($patientId, $orgId) = $this->_getPatientAndOrganisationParam();
     * </code>
     *
     * @return array contraining the patient id and organization id
     */
    protected function _getPatientAndOrganisationParam()
    {
        $patientId = $this->_getParam(MUtil_Model::REQUEST_ID1);
        $orgId     = $this->_getParam(MUtil_Model::REQUEST_ID2);

        return array($patientId, $orgId);
    }

    abstract protected function addTrackUsage($respId, $orgId, $trackId, $baseUrl);

    /**
     * Pops the answers to a survey in a separate window
     */
    public function answerAction()
    {
        // Set menu OFF
        $this->menu->setVisible(false);

        $tokenId = $this->_getIdParam();
        $token   = $this->loader->getTracker()->getToken($tokenId);

        // Set variables for the menu
        $token->applyToMenuSource($this->menu->getParameterSource());

        $this->setTitle(sprintf($this->_('Token answers: %s'), strtoupper($tokenId)));
        $this->addSnippets($token->getAnswerSnippetNames(), 'token', $token, 'tokenId', $tokenId);
    }

    public function autofilterAction()
    {
        $this->initFilter();

        parent::autofilterAction();
    }

    /**
     * Create a new track (never a token as a token is created with the track)
     */
    public function createAction()
    {
        $trackId = $this->_getParam(Gems_Model::TRACK_ID);
        $engine  = $this->loader->getTracker()->getTrackEngine($trackId);

        list($patientId, $orgId) = $this->_getPatientAndOrganisationParam();

        // Set variables for the menu
        $source = $this->menu->getParameterSource();
        $source->setPatient($patientId, $orgId);
        $engine->applyToMenuSource($source);

        $this->html->h2(sprintf($this->_('Adding the %s track to respondent %s: %s'),
                $engine->getTrackName(),
                $patientId,
                $this->getRespondentName()));
        $this->addSnippets($engine->getTrackCreateSnippetNames(),
                'trackEngine', $engine, 'patientId', $patientId, 'organizationId', $orgId);
    }

    public function createRespondentTrackModel($detailed, $action)
    {
        $model = $this->loader->getTracker()->getRespondentTrackModel();

        $model->resetOrder();
        $model->set('gtr_track_name',    'label', $this->_('Track'));
        $model->set('gr2t_track_info',   'label', $this->_('Description'),
            'description', $this->_('Enter the particulars concerning the assignment to this respondent.'));
        $model->set('assigned_by',       'label', $this->_('Assigned by'));
        $model->set('gr2t_start_date',   'label', $this->_('Start'),
        	'dateFormat', 'dd-MM-yyyy',
            'formatFunction', $this->util->getTranslated()->formatDate,
            'default', new Zend_Date());
        $model->set('gr2t_end_date',   'label', $this->_('Ending on'),
        	'dateFormat', 'dd-MM-yyyy',
            'formatFunction', $this->util->getTranslated()->formatDate);
        $model->set('gr2t_reception_code');
        $model->set('gr2t_comment',       'label', $this->_('Comment'));

        return $model;
    }

    public function createTrackModel($detailed, $action)
    {
        $model = new MUtil_Model_TableModel('gems__tracks');

        return $model;
    }

    public function createTokenModel($detailed, $action)
    {
        $model = $this->loader->getTracker()->getTokenModel();

        if (! $detailed) {
            $model->useRespondentTrackAsKey();
        }

        return $model;
    }

    /**
     * Delete a single token, mind you: it can be a SingleSurveyTrack
     */
    public function deleteAction()
    {
        $tokenId = $this->_getIdParam();
        $token   = $this->loader->getTracker()->getToken($tokenId);

        // Set variables for the menu
        $token->applyToMenuSource($this->menu->getParameterSource());

        $this->addSnippets($token->getDeleteSnippetNames(), 'token', $token, 'tokenId', $tokenId);
    }

    /**
     * Edit a single token, mind you: it can be a SingleSurveyTrack
     */
    public function editAction()
    {
        $tokenId = $this->_getIdParam();
        $token   = $this->loader->getTracker()->getToken($tokenId);

        // Set variables for the menu
        $token->applyToMenuSource($this->menu->getParameterSource());

        $this->addSnippets($token->getEditSnippetNames(), 'token', $token, 'tokenId', $tokenId);
    }

    public function emailAction()
    {
        
        $params['mailTarget']   = 'token';
        $params['menu']         = $this->menu;
        $params['model']        = $this->getModel();
        $params['identifier']   = $this->_getIdParam();
        $params['view']         = $this->view;
        $params['routeAction']  = 'show';
        $params['formTitle']    = sprintf($this->_('Send mail to: %s %s'), $this->getTopic(), strtoupper($this->_getIdParam()));
        $params['templateOnly'] = ! $this->loader->getCurrentUser()->hasPrivilege('pr.token.mail.freetext');

        $this->addSnippet('Mail_TokenMailFormSnippet', $params);
        /*
        $model = $this->getModel();

        if ($tokenData = $model->applyRequest($this->getRequest())->loadFirst()) {
            $this->setMenuParameters($tokenData);

            $form = new Gems_Email_OneMailForm(array(
                'escort'       => $this->escort,
                'templateOnly' => ! $this->escort->hasPrivilege('pr.token.mail.freetext')
            ));
            $form->setTokenData($tokenData);

            $wasSent = $form->processRequest($this->getRequest());

            if ($form->hasMessages()) {
                $this->addMessage($form->getMessages());
            }

            if ($wasSent) {
                if ($this->afterSaveRoute($tokenData)) {
                    return null;
                }

            } else {
                $table = new MUtil_Html_TableElement(array('class' => 'formTable'));
                $table->setAsFormLayout($form, true, true);
                $table['tbody'][0][0]->class = 'label';  // Is only one row with formLayout, so all in output fields get class.
                if ($links = $this->createMenuLinks(10)) {
                    // Remove unwanted links
                    unset($links['track.index'], $links['track.edit'], $links['track.delete'], $links['track.questions']);

                    $table->tf(); // Add empty cell, no label
                    $linksCell = $table->tf($links);
                }

                $this->html->h3(sprintf($this->_('Email %s %s'), $this->getTopic(), strtoupper($this->_getIdParam())));
                $this->html[] = $form;
            }


        } else {
            $this->addMessage(sprintf($this->_('%s %s not found.'), $this->getTopic(1), $this->_getParam(MUtil_Model::REQUEST_ID1)));
        }*/
    }

    /**
     * Adds the keys fields as hidden fields to the autosearch fields.
     * Needed as this form has multiple autosearch actions. Can be overruled.
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

        // Get request data up in case of reset.
        if (! $data) {
            $data = $this->getRequest()->getParams();
        }

        if (isset($data[MUtil_Model::REQUEST_ID1])) {
            $i = 1;
            while (isset($data[MUtil_Model::REQUEST_ID . $i])) {
                $keys[] = MUtil_Model::REQUEST_ID . $i;
                $i++;
            }
        } else {
            $keys = $model->getKeys();
        }
        foreach ($keys as $key) {
            $elements[] = new Zend_Form_Element_Hidden($key, array('value' => $data[$key]));
        }

        return $elements;
    }

    /**
     * Get a display version of the patient name
     *
     * @param array $data Already loaded data. If the correct data is not supplied, then the function will retrieve it
     * @return string
     */
    protected function getRespondentName(array $data = array())
    {
        if ($this->respondentName) {
            return $this->respondentName;
        }

        if (! ($data && isset($data['grs_first_name'], $data['grs_surname_prefix'], $data['grs_last_name']))) {
            list($patientId, $orgId) = $this->_getPatientAndOrganisationParam();

            $select = $this->db->select();
            $select->from('gems__respondents')
                    ->joinInner('gems__respondent2org', 'grs_id_user = gr2o_id_user', array())
                    ->where('gr2o_patient_nr = ?', $patientId)
                    ->where('gr2o_id_organization = ?', $orgId);

            $data = $this->db->fetchRow($select);

            if (! $data) {
                return '';
            }
        }

        $this->respondentName = trim($data['grs_first_name'] . ' ' . $data['grs_surname_prefix']) . ' ' . $data['grs_last_name'];
        
        return $this->respondentName;
    }

    public function indexAction()
    {
        $this->initFilter();

        parent::indexAction();
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

        $request = $this->getRequest();
        if ('answer' !== $request->getActionName()) {
            // Tell the system where to return to after a survey has been taken
            $this->loader->getCurrentUser()->setSurveyReturn($request);
        }
    }

    public function initFilter()
    {
        // FROM REQUEST
        if ($param = $this->_getParam(MUtil_Model::REQUEST_ID1)) {
            $this->filterStandard['gr2o_patient_nr'] = $param;
        }
        if ($param = $this->_getParam(MUtil_Model::REQUEST_ID2)) {
            $this->filterStandard['gr2o_id_organization'] = $param;
        }

        if ($param = $this->_getParam(Gems_Model::RESPONDENT_TRACK)) {
            $this->filterStandard['gr2t_id_respondent_track'] = $param;
        }
        if ($param = $this->_getParam(Gems_Model::TRACK_ID)) {
            $this->filterStandard['gtr_id_track'] = $param;
        }

        // FROM VARS
        if ($this->trackType) {
            $this->filterStandard['gtr_track_type'] = $this->trackType;
        }
    }

    /**
     * Pops a PDF - if it exists
     */
    public function pdfAction()
    {
        // Make sure nothing else is output
        $this->initRawOutput();

        // Output the PDF
        $this->loader->getPdf()->echoPdfByTokenId($this->_getIdParam());
    }

    /**
     * Shows the questions in a survey
     */
    public function questionsAction()
    {
        $tokenId = $this->_getIdParam();
        $token   = $this->loader->getTracker()->getToken($tokenId);

        // Set variables for the menu
        $token->applyToMenuSource($this->menu->getParameterSource());

        $this->addSnippets('SurveyQuestionsSnippet', 'surveyId', $token->getSurveyId());
    }

    public function setMenuParameters(array $data)
    {
        $source = $this->menu->getParameterSource();

        if (isset($data['gto_id_token'])) {
            $source->setTokenId($data['gto_id_token']);
        }

        if (isset($data['gr2o_patient_nr'], $data['gr2o_id_organization'])) {
            $source->setPatient($data['gr2o_patient_nr'], $data['gr2o_id_organization']);
        }

        if (isset($data['gr2t_id_respondent_track'])) {
            $source->setRespondentTrackId($data['gr2t_id_respondent_track']);
        }

        if (isset($data['gtr_id_track'])) {
            $source->setTrackId($data['gtr_id_track']);
        }

        // NOW FOR THE VALUES WE NEED BUT HAVE NO CONSTANT
        foreach (array('can_be_taken', 'can_edit', 'can_email', 'is_completed', 'grc_success', 'gsu_id_survey', 'gsu_has_pdf') as $key) {
            if (isset($data[$key])) {
                $source->offsetSet($key, $data[$key]);
            }
        }

        // LASTLY WE GOT A VAR
        if (isset($data['gtr_track_type'])) {
            $source->setTrackType($data['gtr_track_type']);
            $this->trackType = $data['gtr_track_type'];
        }

        // MUtil_Echo::track($source->getArrayCopy());
    }

    /**
     * Show a single token, mind you: it can be a SingleSurveyTrack
     */
    public function showAction()
    {
        $tokenId = $this->_getIdParam();
        $token   = $this->loader->getTracker()->getToken($tokenId);

        // Set variables for the menu
        $token->applyToMenuSource($this->menu->getParameterSource());

        $this->addSnippets($token->getShowSnippetNames(), 'token', $token, 'tokenId', $tokenId);
    }

    public function viewAction()
    {
        $trackId = $this->_getParam(Gems_Model::TRACK_ID);
        $engine  = $this->loader->getTracker()->getTrackEngine($trackId);

        list($patientId, $orgId) = $this->_getPatientAndOrganisationParam();

        // Set variables for the menu
        $source = $this->menu->getParameterSource();
        $source->setPatient($patientId, $orgId);
        $engine->applyToMenuSource($source);

        if ($trackData = $this->db->fetchRow('SELECT * FROM gems__tracks WHERE gtr_id_track = ? ', $trackId)) {
            $this->html->h2(sprintf($this->_('Overview of %s track for respondent %s: %s'), $trackData['gtr_track_name'], $patientId, $this->getRespondentName($trackData)));
            $this->addSnippet('TrackUsageTextDetailsSnippet', 'trackData', $trackData);

            if (! $this->addTrackUsage($patientId, $orgId, $trackId, array())) {
                $this->html->pInfo($this->_('This track is currently not assigned to this respondent.'));
            }
            if ($links = $this->createMenuLinks(10)) {
                $this->html->buttonDiv($links);
            }
            $this->addSnippets($this->addTrackContentSnippets, 'trackData', $trackData);
        } else {
            $this->addMessage(sprintf($this->_('Track %s does not exist.'), $this->_getParam(Gems_Model::TRACK_ID)));
        }
    }
}
