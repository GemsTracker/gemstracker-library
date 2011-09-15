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
 * @subpackage Menu
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: Menu.php 476 2011-09-06 16:26:13Z mjong $
 */

/**
 * This is the generic Menu class to be extended by the project
 *
 * It loads the menu in two stages:
 *
 * 1. $this->loadDefaultMenu()
 * Normally you should not touch this to make upgrading easier
 *
 * 2. $this->loadProjectMenu()
 * This is where you can reorder, add or disable menu items, specific to your projects needs. Be aware that just using
 * different rights in the <project>Roles.php can also do the trick of hiding menu options.
 *
 * @package    Gems
 * @subpackage Menu
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Menu extends Gems_Menu_MenuAbstract
{
    /**
     *
     * @var Gems_Menu_SubMenuItem
     */
    private $_currentMenuItem;

    private $_hiddenPriviliges = array();
    private $_onlyActiveBranchVisible = false;

    /**
     *
     * @var Gems_Menu_ParameterSource
     */
    private $_menuParameters;

    private $_visible = true;

    /**
     * Set output echo on for debugging
     *
     * @var boolean
     */
    static public $verbose = false;

    public function  __construct(GemsEscort $escort)
    {
        parent::__construct($escort);

        //This loads the default menu
        $this->loadDefaultMenu();

        //This is where you plugin your project menu settings
        $this->loadProjectMenu();

        $this->setOnlyActiveBranchVisible();
        $this->applyAcl($escort->acl, $escort->session->user_role);
    }

    private function _findPath($request)
    {
        $find = $this->request2find($request);

        return $this->findItemPath($find);
    }

    /**
     * Shortcut function to create a ask menu, with hidden options.
     *
     * This function is in Gems_Menu instead of AbstractMenu because
     * you should ALWAYS put this menu in the root menu.
     *
     * @param string $label Label for the whole menu
     */
    public function addAskPage($label)
    {
        $page = $this->addPage($label, null, 'ask');

        // Routes for token controller
        $page->addAction(null, null, 'forward');
        $page->addAction(null, null, 'return');
        $page->addAction(null, null, 'token');

        return $page;
    }

    /**
     * Shortcut function to create a contact container.
     *
     * This function is in Gems_Menu instead of AbstractMenu because
     * you should ALWAYS put this menu in the root menu.
     *
     * @param string $label Label for the whole menu
     * @param string $project The project object
     * @param string $privilege The privilige for reporting bugs
     */
    public function addContactPage($label)
    {
        $project = $this->escort->project;

        $page = $this->addPage($label, null, 'contact');

        if (isset($project->contact, $project->contact['supportUrl'])) {
            $page->addAction(sprintf($this->_('About %s'), $project->name), null, 'about');
        }
        // $page->addAction($this->_('Request account'), null, 'request-account');
        if (isset($project->contact, $project->contact['bugsUrl'])) {
            $page->addAction($this->_('Reporting bugs'), 'pr.contact.bugs', 'bugs');
        }
        if (isset($project->contact, $project->contact['supportUrl'])) {
            $page->addAction($this->_('Support'), 'pr.contact.support', 'support');
        }

        return $page;
    }

    /**
     * Shortcut function to create a setup container.
     *
     * This function is in Gems_Menu instead of AbstractMenu because
     * you should ALWAYS put this menu in the root menu.
     *
     * @param string $label Label for the whole menu
     * @param string $privilegeShow The limited privilige (look and edit some items)
     * @param string $privilegeEdits The privilige for being allowed to do anything
     */
    public function addGemsSetupContainer($label)
    {
        $setup = $this->addContainer($label);

        // DATABASE CONTROLLER
        $page = $setup->addPage($this->_('Database'), 'pr.database', 'database');
        $page->addAutofilterAction();
        // Creation not possible
        $showPage = $page->addShowAction('pr.database');
        $showPage->addAction($this->_('Content'), 'pr.database', 'view')
            ->addParameters(MUtil_Model::REQUEST_ID)
            ->setParameterFilter('exists', true);
        $showPage->addAction($this->_('Execute'), 'pr.database.create', 'run')
            ->addParameters(MUtil_Model::REQUEST_ID)
            ->setParameterFilter('script', true);
        $showPage->addDeleteAction('pr.database.delete')
            ->setParameterFilter('exists', true);
        $page->addAction($this->_('Patches'), 'pr.database.patches', 'patch');
        $page->addAction($this->_('Execute new'), 'pr.database.create', 'run-all');
        if (isset($this->escort->project->databaseTranslations)) {
            $page->addAction($this->_('Refresh translateables'), 'pr.database', 'refresh-translations');
        }
        $page->addAction($this->_('Run SQL'), 'pr.database.execute', 'run-sql');

           // PROJECT
        $page = $setup->addPage($this->_('Project setup'), 'pr.project-information', 'project-information');
        $page->addAction($this->_('Errors'),     null, 'errors');
        $page->addAction($this->_('PHP'),        null, 'php');
        $page->addAction($this->_('Project'),    null, 'project');
        $page->addAction($this->_('Session'),    null, 'session');

           // COUNTRIES CONTROLLER
        // $setup->addBrowsePage($this->_('Countries'), 'pr.country', 'country');

        // LANGUAGE CONTROLLER
        // $setup->addPage($this->_('Languages'), 'pr.language', 'language');

        // CONSENT CONTROLLER
        $setup->addBrowsePage($this->_('Consents'), 'pr.consent', 'consent');

        // ORGANIZATIONS CONTROLLER
        $setup->addBrowsePage($this->_('Organizations'),'pr.organization', 'organization');

        // GROUPS CONTROLLER
        $setup->addBrowsePage($this->_('Groups'), 'pr.group', 'group');

        // ROLES CONTROLLER
        $page = $setup->addBrowsePage($this->_('Roles'), 'pr.role', 'role');
        $page->addAction($this->_('ACL'),        null, 'acl');
        $page->addAction($this->_('Assigned'),   null, 'overview');
        $page->addAction($this->_('Privileges'), null, 'privilege');

        // RECEPTION CODE CONTROLLER
        $setup->addBrowsePage($this->_('Reception codes'), 'pr.reception', 'reception');

        // MAIL Server CONTROLLER
        $page = $setup->addBrowsePage($this->_('Email servers'), 'pr.mail.server', 'mail-server');
        // $page->addAction($this->_('Test'), 'pr.mail.server.test', 'test')->addParameters(MUtil_Model::REQUEST_ID);

        // MAIL CONTROLLER
        $setup->addBrowsePage($this->_('Email'), 'pr.mail', 'mail');

        // SURVEY SOURCES CONTROLLER
        $page = $setup->addBrowsePage($this->_('Survey Sources'), 'pr.source', 'source');
        $page->addDeleteAction();
        $page->addAction($this->_('Check status'), null, 'ping')->addParameters(MUtil_Model::REQUEST_ID);
        $page->addAction($this->_('Synchronize surveys'), 'pr.source.synchronize', 'synchronize')->addParameters(MUtil_Model::REQUEST_ID);
        $page->addAction($this->_('Check answers'), 'pr.source.check-answers', 'check')->addParameters(MUtil_Model::REQUEST_ID);
        $page->addAction($this->_('Synchronize all surveys'), 'pr.source.synchronize-all', 'synchronize-all');
        $page->addAction($this->_('Check all answers'), 'pr.source.check-answers-all', 'check-all');

        // SURVEY MAINTENANCE CONTROLLER
        $page = $setup->addPage($this->_('Surveys'), 'pr.survey-maintenance', 'survey-maintenance');
        $page->addEditAction();
        $page->addShowAction();
        $page->addPdfButton($this->_('PDF'), 'pr.survey-maintenance')
                ->addParameters(MUtil_Model::REQUEST_ID)
                ->setParameterFilter('gsu_has_pdf', 1);
        $page->addAction($this->_('Check answers'), 'pr.survey-maintenance.check', 'check')->addParameters(MUtil_Model::REQUEST_ID);
        $page->addAction($this->_('Check all answers'), 'pr.survey-maintenance.check-all', 'check-all');

        $page->addAutofilterAction();

        // TRACK MAINTENANCE CONTROLLER
        $page = $setup->addBrowsePage($this->_('Tracks'), 'pr.track-maintenance', 'track-maintenance');

        // Fields
        $fpage = $page->addPage($this->_('Fields'), 'pr.track-maintenance', 'track-fields')->addNamedParameters(MUtil_Model::REQUEST_ID, 'gtf_id_track');
        $fpage->addAutofilterAction();
        $fpage->addCreateAction('pr.track-maintenance.create')->addNamedParameters(MUtil_Model::REQUEST_ID, 'gtf_id_track');
        $fpage->addShowAction()->addNamedParameters(MUtil_Model::REQUEST_ID, 'gtf_id_track', 'fid', 'gtf_id_field');
        $fpage->addEditAction('pr.track-maintenance.edit')->addNamedParameters('fid', 'gtf_id_field', MUtil_Model::REQUEST_ID, 'gtf_id_track');

        // Standard tracks
        $fpage = $page->addPage($this->_('Rounds'), 'pr.track-maintenance', 'track-rounds')
                ->addNamedParameters(MUtil_Model::REQUEST_ID, 'gro_id_track')
                ->setParameterFilter('gtr_track_type', 'T');
        $fpage->addAutofilterAction();
        $fpage->addCreateAction('pr.track-maintenance.create')->addNamedParameters(MUtil_Model::REQUEST_ID, 'gro_id_track');
        $fpage->addShowAction()->addNamedParameters(MUtil_Model::REQUEST_ID, 'gro_id_track', Gems_Model::ROUND_ID, 'gro_id_round');
        $fpage->addEditAction('pr.track-maintenance.edit')->addNamedParameters(Gems_Model::ROUND_ID, 'gro_id_round', MUtil_Model::REQUEST_ID, 'gro_id_track');

        // Single survey tracks
        $fpage = $page->addPage($this->_('Round'), 'pr.track-maintenance', 'track-round', 'show')
                ->addNamedParameters(MUtil_Model::REQUEST_ID, 'gro_id_track')
                ->setParameterFilter('gtr_track_type', 'S');
        $fpage->addEditAction('pr.track-maintenance.edit')
                ->addNamedParameters(MUtil_Model::REQUEST_ID, 'gro_id_track');

        $page->addAction($this->_('Check assignments'), 'pr.track-maintenance.check', 'check-track')
                ->addParameters(MUtil_Model::REQUEST_ID);

        $page->addAction($this->_('Check all assignments'), 'pr.track-maintenance.check-all', 'check-all');

        // LOG CONTROLLER
        $page = $setup->addPage($this->_('Logging'), 'pr.log', 'log', 'index');
        $page->addAutofilterAction();
        $page->addExcelAction();
        $page->addShowAction();
        $logMaint = $page->addPage($this->_('Maintenance'), 'pr.log.maintenance', 'log-maintenance');
        $logMaint->addAutofilterAction();
        $logMaint->addEditAction('pr.log.maintenance');
        return $setup;
    }

    /**
     * Use this to add a privilege that is not associated with a menu item.
     *
     * @param string $privilege
     * @return Gems_Menu
     */
    public function addHiddenPrivilige($privilege)
    {
        $this->_hiddenPriviliges[$privilege] = sprintf($this->_('Stand-alone privilige: %s'), $privilege);

        return $this;
    }

    public function addLogonOffToken()
    {
        $this->addPage($this->_('Logon'), 'pr.nologin', 'index', 'login')
             ->addAction($this->_('Lost password'), 'pr.nologin', 'resetpassword');
        $optionPage = $this->addPage($this->_('Your account'), 'pr.option.edit', 'option', 'edit');
        $optionPage->addAction($this->_('Activity overview'), 'pr.option.edit', 'overview');
        $optionPage->addAction($this->_('Change password'), 'pr.option.password', 'change-password');
        $this->addAskPage($this->_('Token'));
        $this->addPage($this->_('Logoff'), 'pr.islogin', 'index', 'logoff');

        if ($this->escort->project->multiLocale) {
            // ALLOW LANGUAGE CHANGE
            $this->addPage(null, null, 'language', 'change-ui');
        }

        if ($this->escort instanceof Gems_Project_Organization_MultiOrganizationInterface) {
            // ALLOW ORGANIZATION CHANGE WITH PROPER RIGHTS
            $this->addPage(null, 'pr.organization-switch', 'organization', 'change-ui');
        }

    }

    public function addRespondentPage($label)
    {
        // MAIN RESPONDENTS ITEM
        $page = $this->addPage($label, 'pr.respondent', 'respondent');
        $page->addAutofilterAction();
        $page->addCreateAction('pr.respondent.create');
        $page->addShowAction()->addNamedParameters(MUtil_Model::REQUEST_ID, 'gr2o_patient_nr');

        /*
        iff(
            is('gr2o_id_organization', $this->escort->getCurrentOrganization()),
            aget(MUtil_Model::REQUEST_ID, 'gr2o_patient_nr'),
            aget(MUtil_Model::REQUEST_ID . '1', 'gr2o_patient_nr', MUtil_Model::REQUEST_ID . '2', 'gr2o_id_organization')
            );
        */

        $page->addEditAction('pr.respondent.edit')->addNamedParameters(MUtil_Model::REQUEST_ID, 'gr2o_patient_nr');

        if ($this->escort instanceof Gems_Project_Tracks_SingleTrackInterface) {

            $trType = 'T';
            $subPage = $page->addPage($this->_('Track'), 'pr.track', 'track', 'show-track')
                ->addNamedParameters(MUtil_Model::REQUEST_ID, 'gr2o_patient_nr')
                ->addHiddenParameter(Gems_Model::TRACK_ID, $this->escort->getTrackId(), 'gtr_track_type', 'T');

            $tkPages[$trType] = $subPage->addAction($this->_('Token'), 'pr.token', 'show')
                    ->addNamedParameters(MUtil_Model::REQUEST_ID, 'gto_id_token')
                    ->setParameterFilter('gtr_track_type', $trType, Gems_Model::ID_TYPE, 'token');
            $subPage->addAction($this->_('Add'), 'pr.track.create', 'create')
                ->addNamedParameters(MUtil_Model::REQUEST_ID, 'gr2o_patient_nr', Gems_Model::TRACK_ID, 'gtr_id_track')
                ->setParameterFilter('gtr_track_type', $trType, 'track_can_be_created', 1)
                ->addHiddenParameter('track_can_be_created', 1);
            $subPage->addAction($this->_('Preview'), 'pr.track', 'view')
                ->addNamedParameters(MUtil_Model::REQUEST_ID, 'gr2o_patient_nr', Gems_Model::TRACK_ID, 'gtr_id_track')
                ->setParameterFilter('gtr_track_type', $trType, 'track_can_be_created', 1)
                ->addHiddenParameter('track_can_be_created', 1);

        } else {

            $trPage = $page->addPage($this->_('Tracks'), 'pr.track', 'track');
            $trType = 'T';

            $trPage->addNamedParameters(MUtil_Model::REQUEST_ID, 'gr2o_patient_nr');
            $trPage->addAutofilterAction();

            /*
             iff(is('gtr_track_type', $trType), aget(MUtil_Model::REQUEST_ID, 'gr2o_patient_nr', Gems_Model::TRACK_ID, 'gtr_id_track'))
             */
            $trPage->addAction($this->_('Add'), 'pr.track.create', 'create')
                ->addNamedParameters(MUtil_Model::REQUEST_ID, 'gr2o_patient_nr', Gems_Model::TRACK_ID, 'gtr_id_track')
                ->setParameterFilter('gtr_track_type', $trType);

            $trPage->addAction($this->_('Assignments'), 'pr.track', 'view')
                ->addNamedParameters(MUtil_Model::REQUEST_ID, 'gr2o_patient_nr', Gems_Model::TRACK_ID, 'gtr_id_track')
                ->setParameterFilter('gtr_track_type', $trType);

            $tkPages[$trType] = $trPage->addAction($this->_('Show'), 'pr.track', 'show-track')
                ->addNamedParameters(MUtil_Model::REQUEST_ID, 'gr2o_patient_nr', Gems_Model::RESPONDENT_TRACK, 'gr2t_id_respondent_track')
                ->setParameterFilter('gtr_track_type', $trType);

            $tkPages[$trType]->addAction($this->_('Token'), 'pr.token', 'show')
                ->setNamedParameters(MUtil_Model::REQUEST_ID, 'gto_id_token')
                ->setParameterFilter('gtr_track_type', $trType, Gems_Model::ID_TYPE, 'token');

            $trPage->addAction($this->_('Edit'), 'pr.track.edit', 'edit-track')
                ->addNamedParameters(MUtil_Model::REQUEST_ID, 'gr2o_patient_nr', Gems_Model::RESPONDENT_TRACK, 'gr2t_id_respondent_track')
                ->setParameterFilter('gtr_track_type', $trType, 'can_edit', 1);

            $trPage->addAction($this->_('Delete'), 'pr.track.delete', 'delete-track')
                ->addNamedParameters(MUtil_Model::REQUEST_ID, 'gr2o_patient_nr', Gems_Model::RESPONDENT_TRACK, 'gr2t_id_respondent_track')
                ->setParameterFilter('gtr_track_type', $trType, 'can_edit', 1);

            if ($this->escort instanceof Gems_Project_Tracks_StandAloneSurveysInterface) {
                $trPage = $page->addPage($this->_('Surveys'), 'pr.survey', 'survey');
                $trType = 'S';

                $trPage->addNamedParameters(MUtil_Model::REQUEST_ID, 'gr2o_patient_nr');
                $trPage->addAutofilterAction();

                $trPage->addAction($this->_('Add'), 'pr.survey.create', 'create')
                    ->addNamedParameters(MUtil_Model::REQUEST_ID, 'gr2o_patient_nr', Gems_Model::TRACK_ID, 'gtr_id_track')
                    ->setParameterFilter('gtr_track_type', $trType);

                $trPage->addAction($this->_('Assigned'), 'pr.survey', 'view')
                    ->addNamedParameters(MUtil_Model::REQUEST_ID, 'gr2o_patient_nr', Gems_Model::TRACK_ID, 'gtr_id_track')
                    ->setParameterFilter('gtr_track_type', $trType);

                $tkPages[$trType] = $trPage;

                $tkPages[$trType]->addShowAction('pr.survey')
                    ->setNamedParameters(MUtil_Model::REQUEST_ID, 'gto_id_token')
                    ->setParameterFilter('gtr_track_type', $trType, Gems_Model::ID_TYPE, 'token');
            }
        }

        foreach ($tkPages as $trType => $tkPage) {

            $tkPage->addEditAction('pr.token.edit')
                ->setNamedParameters(MUtil_Model::REQUEST_ID, 'gto_id_token')
                ->setParameterFilter('gtr_track_type', $trType, 'grc_success', 1, Gems_Model::ID_TYPE, 'token');

            $tkPage->addDeleteAction('pr.token.delete')
                    ->addNamedParameters(MUtil_Model::REQUEST_ID, 'gto_id_token')
                    ->setParameterFilter('gtr_track_type', $trType, 'grc_success', 1, Gems_Model::ID_TYPE, 'token');

            $tkPage->addButtonOnly($this->_('Fill in'), 'pr.ask', 'ask', 'take')
                    ->addNamedParameters(MUtil_Model::REQUEST_ID, 'gto_id_token')
                    ->setParameterFilter('can_be_taken', 1, Gems_Model::ID_TYPE, 'token')
                    ->addHiddenParameter('delay', 0);
            $tkPage->addPdfButton($this->_('Print PDF'), 'pr.token.print')
                    ->addNamedParameters(MUtil_Model::REQUEST_ID, 'gto_id_token')
                    ->setParameterFilter('gsu_has_pdf', 1, Gems_Model::ID_TYPE, 'token');
            $tkPage->addAction($this->_('E-Mail now!'), 'pr.token.mail', 'email')
                    ->addNamedParameters(MUtil_Model::REQUEST_ID, 'gto_id_token')
                    ->setParameterFilter('gtr_track_type', $trType, 'can_be_taken', 1, 'can_email', 1, Gems_Model::ID_TYPE, 'token');
            $tkPage->addAction($this->_('Preview'), 'pr.project.questions', 'questions')
                    ->addNamedParameters(MUtil_Model::REQUEST_ID, 'gto_id_token')
                    ->setParameterFilter('gtr_track_type', $trType, Gems_Model::ID_TYPE, 'token');
            $tkPage->addActionButton($this->_('Answers'), 'pr.token.answers', 'answer')
                    ->addNamedParameters(MUtil_Model::REQUEST_ID, 'gto_id_token')
                    ->setParameterFilter('gtr_track_type', $trType, 'is_completed', 1, Gems_Model::ID_TYPE, 'token')
                    ->set('target', MUtil_Model::REQUEST_ID);
        }

        $page->addDeleteAction('pr.respondent.delete')->addNamedParameters(MUtil_Model::REQUEST_ID, 'gr2o_patient_nr');

        return $page;
    }

    /**
     *
     * @param Zend_Controller_Request_Abstract|array $request
     * @return Gems_Menu_SubMenuItem|null
     */
    public function find($request)
    {
        $find = $this->request2find($request);

        return $this->findItem($find, true);
    }

    public function findAll($request)
    {
        $find = $this->request2find($request);

        $results = array();

        $this->findItems($find, $results);

        if ($results) {
            if (count($results) == 1) {
                return reset($results);
            }

            return new MUtil_Html_MultiWrapper($results);
        }
    }

    public function findFirst($request)
    {
        $find = $this->request2find($request);

        return $this->findItem($find, false);
    }

    public function getActivePath(Zend_Controller_Request_Abstract $request)
    {
        $activePath = $this->_findPath($request);

        return array_reverse($activePath);
    }

    /**
     *
     * @return Gems_Menu_SubMenuItem
     */
    public function getCurrent()
    {
        return $this->_currentMenuItem;
    }

    public function getCurrentChildren()
    {
        if ($current = $this->getCurrent()) {
            return $current->getChildren();
        } else {
            return array();
        }
    }

    /**
     *
     * @return Gems_Menu_MenuAbstract
     */
    public function getCurrentParent()
    {
        if ($current = $this->getCurrent()) {
            return $current->getParent();
        }
    }

    /**
     *
     * @return Gems_Menu_MenuList
     */
    public function getMenuList()
    {
        return new Gems_Menu_MenuList($this);
    }

    /**
     * Use to set parameters that will be used when
     * drawing the navigation menu.
     *
     * @return Gems_Menu_ParameterSource
     */
    public function getParameterSource()
    {
        if (! $this->_menuParameters) {
            $this->_menuParameters = new Gems_Menu_ParameterSource();
        }

        return $this->_menuParameters;
    }

    /**
     * Returns a (unique) list of privileges that are used in the menu
     *
     * @return array
     */
    public function getUsedPrivileges()
    {
        $privileges = $this->_hiddenPriviliges;

        $this->_addUsedPrivileges($privileges, '');

        return $privileges;
    }

    public function isTopLevel()
    {
        return true;
    }

    public function isVisible()
    {
        return $this->_visible;
    }

    /**
     * This is where we load the default menu, be very careful to overload this function
     * as it makes upgrading a lot more difficult
     */
    public function loadDefaultMenu()
    {
        // MAIN RESPONDENTS ITEM
        $this->addRespondentPage($this->escort->_('Respondents'));

        // MAIN PLANNING ITEM
        $this->addPlanPage($this->escort->_('Overview'));

        // MAIN RESULTS ITEM
        // $menu->addPage($this->_('Results'), 'pr.result', 'result');
        // $menu->addPage($this->_('Invite'), 'pr.invitation', 'invitation');

        // PROJECT INFO
        $this->addProjectPage($this->escort->_('Project'));

        // MAIN STAFF ITEM
        $this->addStaffPage($this->escort->_('Staff'), array('order'=>40));

        // SETUP CONTAINER
        $this->addGemsSetupContainer($this->escort->_('Setup'));

        // OTHER ITEMS
        $this->addLogonOffToken();

        // CONTACT MENU
        $this->addContactPage($this->escort->_('Contact'));

        // Privileges not associated with menu item
        $this->addHiddenPrivilige('pr.plan.choose-org');
        $this->addHiddenPrivilige('pr.plan.mail-as-application');
        $this->addHiddenPrivilige('pr.respondent.result');
        $this->addHiddenPrivilige('pr.respondent.who');
        $this->addHiddenPrivilige('pr.staff.edit.all');
        $this->addHiddenPrivilige('pr.staff.see.all');
        $this->addHiddenPrivilige('pr.token.mail.freetext');


        //Changelog added as button only
        $this->addButtonOnly($this->_('Changelog'),  'pr.project-information.changelog', 'project-information','changelog');

        // Special page for automated e-mail cronjob
        $this->addPage(null, null, 'email', 'index');
    }

    /**
     * Plug your project menu into this function call
     */
    public function loadProjectMenu()
    {
    }

    protected function request2find($request)
    {
        if (is_array($request)) {
            return $request;
        }

        if ($request instanceof Zend_Controller_Request_Abstract) {
            $find['action']     = $request->getActionName();
            $find['controller'] = $request->getControllerName();
        } else {
            throw new Gems_Exception_Coding('Not a valid menu search request!');
        }
        // MUtil_Echo::r($find);

        return $find;
    }

    public function setCurrent(Gems_Menu_SubMenuItem $item)
    {
        $this->_currentMenuItem = $item;
    }

    public function setOnlyActiveBranchVisible($value = true)
    {
        $this->_onlyActiveBranchVisible = $value;

        return $this;
    }

    public function setVisible($value = true)
    {
        $this->_visible = $value;

        return $this;
    }

    /**
     * Generates a Zend_Navigation object from the current menu
     *
     * @param Zend_Controller_Request_Abstract $request
     * @param mixed $actionController
     * @return Zend_Navigation
     */
    public function toZendNavigation(Zend_Controller_Request_Abstract $request, $actionController = null)
    {
        if ($this->_onlyActiveBranchVisible) {
            $activePath = $this->_findPath($request);

            // MUtil_Echo::r($activePath);
            $this->setBranchVisible($activePath);
        }

        $parameterSources = func_get_args();

        if ($this->_menuParameters) {
            // array_unshift($parameterSources, $this->_menuParameters);
            // MUtil_echo::track($this->_menuParameters);
            $parameterSources[] = $this->_menuParameters;
        }

        // self::$verbose = true;

        $nav = new Zend_Navigation($this->_toNavigationArray($parameterSources));

        // MUtil_Echo::r($this->_toNavigationArray($parameterSources));

        return $nav;
    }
}
