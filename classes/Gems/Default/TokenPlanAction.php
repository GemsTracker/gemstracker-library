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
 * @since      Class available since version 1.1
 */
class Gems_Default_TokenPlanAction extends Gems_Controller_BrowseEditAction
{
    public $sortKey = array(
        'gto_valid_from'          => SORT_ASC,
        'gto_mail_sent_date'      => SORT_ASC,
        'respondent_name'         => SORT_ASC,
        'gr2o_patient_nr'         => SORT_ASC,
        'calc_track_name'         => SORT_ASC,
        'calc_track_info'         => SORT_ASC,
        'calc_round_description'  => SORT_ASC,
        'gto_round_order'         => SORT_ASC,
        );

    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Adds a button column to the model, if such a button exists in the model.
     *
     * @param MUtil_Model_Bridge_TableBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function addBrowseTableColumns(MUtil_Model_Bridge_TableBridge $bridge, MUtil_Model_ModelAbstract $model)
    {
        $HTML  = MUtil_Html::create();

        $model->set('gto_id_token', 'formatFunction', 'strtoupper');

        // Row with dates and patient data
        $bridge->gtr_track_type; // Data needed for buttons

        $bridge->setDefaultRowClass(MUtil_Html_TableElement::createAlternateRowClass('even', 'even', 'odd', 'odd'));
        $tr = $bridge->tr();
        $tr->appendAttrib('class', $bridge->row_class);
        $tr->appendAttrib('title', $bridge->gto_comment);

        $bridge->addColumn($this->getTokenLinks($bridge), ' ')->rowspan = 2; // Space needed because TableElement does not look at rowspans
        $bridge->addSortable('gto_valid_from');
        $bridge->addSortable('gto_valid_until');

        $bridge->addSortable('gto_id_token');
        // $bridge->addSortable('gto_mail_sent_num', $this->_('Contact moments'))->rowspan = 2;

        $bridge->addMultiSort('gr2o_patient_nr', $HTML->raw('; '), 'respondent_name');;
        $bridge->addMultiSort('ggp_name', array($this->getActionLinks($bridge)));

        $tr = $bridge->tr();
        $tr->appendAttrib('class', $bridge->row_class);
        $tr->appendAttrib('title', $bridge->gto_comment);
        $bridge->addSortable('gto_mail_sent_date');
        $bridge->addSortable('gto_completion_time');
        $bridge->addSortable('gto_mail_sent_num', $this->_('Contact moments'));


        if ($this->escort instanceof Gems_Project_Tracks_SingleTrackInterface) {
            $bridge->addMultiSort('calc_round_description', $HTML->raw('; '), 'gsu_survey_name');
        } else {
            $model->set('calc_track_info', 'tableDisplay', 'smallData');
            $model->set('calc_round_description', 'tableDisplay', 'smallData');
            $bridge->addMultiSort(
                'calc_track_name', 'calc_track_info',
                array($bridge->calc_track_name->if($HTML->raw(' &raquo; ')), ' '),
                'gsu_survey_name', 'calc_round_description');
        }

        $bridge->addSortable('assigned_by');
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
        // MUtil_Model::$verbose = true;
        $model = $this->loader->getTracker()->getTokenModel();
        $model->setCreate(false);

        $model->set('gr2o_patient_nr',       'label', $this->_('Respondent'));
        $model->set('gto_round_description', 'label', $this->_('Round / Details'));
        $model->set('gto_valid_from',        'label', $this->_('Valid from'));
        $model->set('gto_valid_until',       'label', $this->_('Valid until'));
        $model->set('gto_mail_sent_date',    'label', $this->_('Contact date'));
        $model->set('respondent_name',       'label', $this->_('Name'));

        return $model;
    }

    public function emailAction()
    {
        $model   = $this->getModel();

        // Set the request cache to use the search params from the index action
        $this->getCachedRequestData(true, 'index', true);

        // Load the filters
        $this->_applySearchParameters($model);

        $sort = array(
            'grs_email'          => SORT_ASC,
            'grs_first_name'     => SORT_ASC,
            'grs_surname_prefix' => SORT_ASC,
            'grs_last_name'      => SORT_ASC,
            'gto_valid_from'     => SORT_ASC,
            'gto_round_order'    => SORT_ASC,
            'gsu_survey_name'    => SORT_ASC,
        );

        if ($tokensData = $model->load(true, $sort)) {

            $params['mailTarget']           = 'token';
            $params['menu']                 = $this->menu;
            $params['model']                = $model;
            $params['identifier']           = $this->_getIdParam();
            $params['view']                 = $this->view;
            $params['routeAction']          = $this->getAfterSaveRoute(array());
            $params['formTitle']            = sprintf($this->_('Send mail to: %s'), $this->getTopic());
            $params['templateOnly']         = ! $this->loader->getCurrentUser()->hasPrivilege('pr.token.mail.freetext');
            $params['multipleTokenData']    = $tokensData;

            $this->addSnippet('Mail_TokenBulkMailFormSnippet', $params);
            /*
            $form = new Gems_Email_MultiMailForm(array(
                'escort' => $this->escort,
                'templateOnly' => ! $this->escort->hasPrivilege('pr.token.mail.freetext'),
            ));
            $form->setTokensData($tokensData);

            $wasSent = $form->processRequest($this->getRequest());

            if ($form->hasMessages()) {
                $this->addMessage($form->getMessages());
            }

            if ($wasSent) {
                if ($this->afterSaveRoute(array())) {
                    return null;
                }

            } else {
                $table = new MUtil_Html_TableElement(array('class' => 'formTable'));
                $table->setAsFormLayout($form, true, true);
                $table['tbody'][0][0]->class = 'label';  // Is only one row with formLayout, so all in output fields get class.
                if ($links = $this->createMenuLinks(10)) {
                    $table->tf(); // Add empty cell, no label
                    $linksCell = $table->tf($links);
                }

                $this->html->h3(sprintf($this->_('Email %s'), $this->getTopic()));
                $this->html[] = $form;
            }*/


        } else {
            $this->addMessage($this->_('No tokens found.'));
        }
    }

    public function getActionLinks(MUtil_Model_Bridge_TableBridge $bridge)
    {
        // Get the other token buttons
        if ($menuItems = $this->menu->findAll(array('controller' => array('track', 'survey'), 'action' => array('email', 'answer'), 'allowed' => true))) {
            $buttons = $menuItems->toActionLink($this->getRequest(), $bridge);
            $buttons->appendAttrib('class', 'rightFloat');
        } else {
            $buttons = null;
        }
        // Add the ask button
        if ($menuItem = $this->menu->find(array('controller' => 'ask', 'action' => 'take', 'allowed' => true))) {
            $askLink = $menuItem->toActionLink($this->getRequest(), $bridge);
            $askLink->appendAttrib('class', 'rightFloat');

            if ($buttons) {
                // Show previous link if show, otherwise show ask link
                $buttons = array($buttons, $askLink);
            } else {
                $buttons = $askLink;
            }
        }

        return $buttons;
    }

    /**
     * Returns tokenplan specific autosearch fields. Can be overruled.
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

        if ($elements) {
            $elements[] = null; // break into separate spans
        }

        $dates = array(
            '_gto_valid_from gto_valid_until'
                                  => $this->_('Is valid during'),
            '-gto_valid_from gto_valid_until'
                                  => $this->_('Is valid within'),
            'gto_valid_from'      => $this->_('Valid from'),
            'gto_valid_until'     => $this->_('Valid until'),
            'gto_mail_sent_date'  => $this->_('E-Mailed on'),
            'gto_completion_time' => $this->_('Completion date'),
            );

        $element = $this->_createSelectElement('dateused', $dates);
        $element->setLabel($this->_('For date'));
        $elements[] = $element;

        $options = array();
        $options['label'] = $this->_('from');
        MUtil_Model_Bridge_FormBridge::applyFixedOptions('date', $options);
        $elements[] = new Gems_JQuery_Form_Element_DatePicker('datefrom', $options);

        $options['label'] = ' ' . $this->_('until');
        $elements[] = new Gems_JQuery_Form_Element_DatePicker('dateuntil', $options);

        $elements[] = null; // break into separate spans


        return array_merge($elements, $this->getAutoSearchSelectElements());
    }

    protected function getAutoSearchSelectElements()
    {
        $user        = $this->loader->getCurrentUser();
        $allowedOrgs = $user->getRespondentOrganizations();
        $multiOrg    = count($allowedOrgs) > 1;

        $elements[] = $this->_('Select:');
        $elements[] = MUtil_Html::create('br');

        if ($multiOrg) {
            $orgWhere = $user->getRespondentOrgWhere('gtr_organizations');
        } else {
            $orgId = $user->getCurrentOrganizationId();
            $orgWhere = "INSTR(gtr_organizations, '|$orgId|') > 0";
        }

        // Add track selection
        if ($this->escort instanceof Gems_Project_Tracks_MultiTracksInterface) {
            $sql = "SELECT gtr_id_track, gtr_track_name
                FROM gems__tracks
                WHERE gtr_active=1 AND gtr_track_type='T' AND $orgWhere
                ORDER BY gtr_track_name";
            $elements[] = $this->_createSelectElement('gto_id_track', $sql, $this->_('(all tracks)'));
        }

        $sql = "SELECT DISTINCT gro_round_description, gro_round_description
                    FROM gems__rounds INNER JOIN gems__tracks ON gro_id_track = gtr_id_track
                    WHERE gro_active=1 AND
                        LENGTH(gro_round_description) > 0 AND
                        gtr_active=1 AND
                        gtr_track_type='T' AND
                        $orgWhere
                    ORDER BY gro_round_description";
        $elements[] = $this->_createSelectElement('gto_round_description', $sql, $this->_('(all rounds)'));

        $sql = "SELECT DISTINCT gsu_id_survey, gsu_survey_name
                    FROM gems__surveys INNER JOIN gems__rounds ON gsu_id_survey = gro_id_survey
                        INNER JOIN gems__tracks ON gro_id_track = gtr_id_track
                    WHERE gsu_active=1 AND
                        gro_active=1 AND
                        gtr_active=1 AND
                        $orgWhere
                    ORDER BY gsu_survey_name";
        /* TODO: use this when we can update this list using ajax
        if (isset($data['gsu_id_primary_group'])) {
            $sql .= $this->db->quoteInto(" AND gsu_id_primary_group = ?", $data['gsu_id_primary_group']);
        } // */
        $elements[] = $this->_createSelectElement('gto_id_survey', $sql, $this->_('(all surveys)'));

        $options = array(
            'all'       => $this->_('(all actions)'),
            'open'      => $this->_('Open'),
            'notmailed' => $this->_('Not emailed'),
            'tomail'    => $this->_('To email'),
            'toremind'  => $this->_('Needs reminder'),
            'hasnomail' => $this->_('Missing email'),
            'toanswer'  => $this->_('Yet to Answer'),
            'answered'  => $this->_('Answered'),
            'missed'    => $this->_('Missed'),
            'removed'   => $this->_('Removed'),
            );
        $elements[] = $this->_createSelectElement('main_filter', $options);

        $sql = "SELECT DISTINCT ggp_id_group, ggp_name
                    FROM gems__groups INNER JOIN gems__surveys ON ggp_id_group = gsu_id_primary_group
                        INNER JOIN gems__rounds ON gsu_id_survey = gro_id_survey
                        INNER JOIN gems__tracks ON gro_id_track = gtr_id_track
                    WHERE ggp_group_active = 1 AND
                        gro_active=1 AND
                        gtr_active=1 AND
                        gtr_track_type='T' AND
                        $orgWhere
                    ORDER BY ggp_name";
        $elements[] = $this->_createSelectElement('gsu_id_primary_group', $sql, $this->_('(all fillers)'));

        // Select organisation
        if ($multiOrg) {
            $elements[] = $this->_createSelectElement(
                    'gto_id_organization',
                    $allowedOrgs,
                    $this->_('(all organizations)')
                    );
        }

        if ($multiOrg) {
            $orgWhere = "gr2t_id_organization IN (" . implode(", ", array_keys($allowedOrgs)) . ")";
        } else {
            $orgWhere = "gr2t_id_organization = " . intval($user->getCurrentOrganizationId());
        }
        $sql = "SELECT DISTINCT gsf_id_user, CONCAT(
                        COALESCE(gems__staff.gsf_last_name, ''),
                        ', ',
                        COALESCE(gems__staff.gsf_first_name, ''),
                        COALESCE(CONCAT(' ', gems__staff.gsf_surname_prefix), '')
                    ) AS gsf_name
                FROM gems__staff INNER JOIN gems__respondent2track ON gsf_id_user = gr2t_created_by
                WHERE $orgWhere AND
                    gr2t_active = 1
                ORDER BY 2";
        $elements[] = $this->_createSelectElement('gr2t_created_by', $sql, $this->_('(all staff)'));

        return $elements;
    }

    protected function getDataFilter(array $data)
    {
        // MUtil_Model::$verbose = true;

        //Add default filter
        $filter = array();
        if ($where = Gems_Snippets_AutosearchFormSnippet::getPeriodFilter($data, $this->db)) {
            // MUtil_Echo::track($where);
            $filter[] = $where;
        }

        if (! isset($data['gto_id_organization'])) {
            $filter['gto_id_organization'] = $this->loader->getCurrentUser()->getRespondentOrgFilter();
        }
        $filter['gtr_active']  = 1;
        $filter['gsu_active']  = 1;
        $filter['grc_success'] = 1;

        if (isset($data['main_filter'])) {
            switch ($data['main_filter']) {
                case 'answered':
                    $filter[] = 'gto_completion_time IS NOT NULL';
                    break;

                case 'hasnomail':
                    $filter[] = sprintf(
                            "(grs_email IS NULL OR grs_email = '' OR grs_email NOT RLIKE '%s') AND
                                ggp_respondent_members = 1",
                            str_replace('\'', '\\\'', trim(MUtil_Validate_SimpleEmail::EMAIL_REGEX, '/'))
                            );
                    $filter[] = '(gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP)';
                    $filter['gto_completion_time'] = null;
                    break;

                case 'missed':
                    $filter[] = 'gto_valid_from <= CURRENT_TIMESTAMP';
                    $filter[] = 'gto_valid_until < CURRENT_TIMESTAMP';
                    $filter['gto_completion_time'] = null;
                    break;

                case 'notmailed':
                    $filter['gto_mail_sent_date'] = null;
                    $filter[] = '(gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP)';
                    $filter['gto_completion_time'] = null;
                    break;

                case 'open':
                    $filter['gto_completion_time'] = null;
                    $filter[] = 'gto_valid_from <= CURRENT_TIMESTAMP';
                    $filter[] = '(gto_valid_until >= CURRENT_TIMESTAMP OR gto_valid_until IS NULL)';
                    break;

                // case 'other':
                //    $filter[] = "grs_email IS NULL OR grs_email = '' OR ggp_respondent_members = 0";
                //    $filter['can_email'] = 0;
                //    $filter[] = '(gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP)';
                //    break;

                case 'removed':
                    $filter['grc_success'] = 0;
                    break;

                case 'toanswer':
                    $filter[] = 'gto_completion_time IS NULL';
                    break;

                case 'tomail':
                    $filter[] = sprintf(
                            "grs_email IS NOT NULL AND
                                grs_email != '' AND
                                grs_email RLIKE '%s' AND
                                ggp_respondent_members = 1",
                            str_replace('\'', '\\\'', trim(MUtil_Validate_SimpleEmail::EMAIL_REGEX, '/'))
                            );
                    //$filter[] = "grs_email IS NOT NULL AND grs_email != '' AND ggp_respondent_members = 1";
                    $filter['gto_mail_sent_date'] = null;
                    $filter[] = '(gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP)';
                    $filter['gto_completion_time'] = null;
                    break;

                case 'toremind':
                    // $filter['can_email'] = 1;
                    $filter[] = 'gto_mail_sent_date < CURRENT_TIMESTAMP';
                    $filter[] = '(gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP)';
                    $filter['gto_completion_time'] = null;
                    break;

                default:
                    break;

            }
        }

        if (isset($data['date_type'], $data['date_used'])) {
            // Check for period selected
            switch ($data['date_type']) {
                case 'W':
                    $period_unit  = 'WEEK';
                    break;
                case 'M':
                    $period_unit  = 'MONTH';
                    break;
                case 'Y':
                    $period_unit  = 'YEAR';
                    break;
                default:
                    $period_unit  = 'DAY';
                    break;
            }

            if (! $data['date_used']) {
                $data['date_used'] = 'gto_valid_from';
            }

            $date_field  = $this->db->quoteIdentifier($data['date_used']);
            $date_filter = "DATE_ADD(CURRENT_DATE, INTERVAL ? " . $period_unit . ")";
            $filter[] = $this->db->quoteInto($date_field . ' >= '.  $date_filter, intval($data['period_start']));
            $filter[] = $this->db->quoteInto($date_field . ' <= '.  $date_filter, intval($data['period_end']));
        }

        // MUtil_Echo::track($filter);
        return $filter;
    }

    public function getDefaultSearchData()
    {
        $inFormat = MUtil_Model_Bridge_FormBridge::getFixedOption('date', 'dateFormat');
        $now      = new MUtil_Date();

        return array(
            'datefrom'            => $now->toString($inFormat),
            'dateused'            => '_gto_valid_from gto_valid_until',
            'dateuntil'           => $now->toString($inFormat),
            'main_filter'         => 'all',
        );
    }

    public function getTokenLinks(MUtil_Model_Bridge_TableBridge $bridge)
    {
        // Get the token buttons
        if ($menuItems = $this->menu->findAll(array('controller' => array('track', 'survey'), 'action' => 'show', 'allowed' => true))) {
            $buttons = $menuItems->toActionLink($this->getRequest(), $bridge, $this->_('+'));
            $buttons->title = $bridge->gto_id_token->strtoupper();

            return $buttons;
        }
    }

    public function getTopic($count = 1)
    {
        return $this->plural('token', 'tokens', $count);
    }

    public function getTopicTitle()
    {
        return $this->_('Token planning');
    }

    /**
     * Default overview action
     */
    public function indexAction()
    {
        // MUtil_Model::$verbose = true;

        // Check for unprocessed tokens
        $filter = $this->getCachedRequestData(true);
        $orgId  = array_key_exists('gto_id_organization', $filter) ? $filter['gto_id_organization'] : null;
        $this->loader->getTracker()->processCompletedTokens(null, $this->session->user_id, $orgId, true);

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

        // Tell the system where to return to after a survey has been taken, make sure to remove (auto)search parameters from the url
        $request   = $this->getRequest();
        $allParams = $request->getParams();
        $allowed   = array_flip(array($request->getModuleKey(), $request->getControllerKey(), $request->getActionKey()));
        $urlParams = array_intersect_key($allParams, $allowed);
        $this->loader->getCurrentUser()->setSurveyReturn($urlParams);
    }
}
