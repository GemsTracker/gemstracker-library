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
 * @version    $Id$
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
class Gems_Menu extends Gems_Menu_MenuAbstract implements MUtil_Html_HtmlInterface
{
    /**
     *
     * @var Gems_Menu_SubMenuItem
     */
    private $_currentMenuItem;

    private $_hiddenPrivileges = array();
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
        $this->applyAcl($escort->acl, $escort->getLoader()->getCurrentUser()->getRole());
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
        $page->addAction(null, null, 'to-survey')->setModelParameters(1);
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
     * @return Gems_Menu_MenuAbstract The new contact page
     */
    public function addContactPage($label)
    {
        $project = $this->escort->project;

        $page = $this->addPage($label, null, 'contact');

        $page->addAction(sprintf($this->_('About %s'), $project->getName()), null, 'about');
        $page->addAction(sprintf($this->_('About %s'), $this->_('GemsTracker')), 'pr.contact.gems', 'gems');

        if ($project->hasBugsUrl()) {
            $page->addAction($this->_('Reporting bugs'), 'pr.contact.bugs', 'bugs');
        }
        if ($project->hasAnySupportUrl()) {
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
     * @param string $privilegeShow The limited privilege (look and edit some items)
     * @param string $privilegeEdits The privilege for being allowed to do anything
     */
    public function addGemsSetupContainer($label)
    {
        $setup = $this->addContainer($label);

        // PROJECT LEVEL
        $cont = $setup->addProjectInfoPage($this->_('Project setup'));
        //UPGRADES CONTROLLER
        $page = $cont->addPage($this->_('Upgrade'), 'pr.upgrade', 'upgrade', 'index');
        $page->addPage(sprintf($this->_('Changelog %s'), 'GemsTracker'), 'pr.upgrade', 'project-information', 'changeloggt');
        $page->addPage(sprintf($this->_('Changelog %s'), $this->escort->project->getName()), 'pr.upgrade', 'project-information', 'changelog');
        $show = $page->addAction($this->_('Show'), null, 'show')->setNamedParameters('id','context');
        $page->addAction($this->_('Execute all'), 'pr.upgrade.all', 'execute-all')->setModelParameters(1);
        $show->addActionButton($this->_('Execute this'), 'pr.upgrade.one', 'execute-one')->setModelParameters(1)->addNamedParameters('from','from','to','to');
        $show->addActionButton($this->_('Execute from here'), 'pr.upgrade.from', 'execute-from')->setModelParameters(1)->addNamedParameters('from','from');
        $show->addActionButton($this->_('Execute to here'), 'pr.upgrade.to', 'execute-to')->setModelParameters(1)->addNamedParameters('to','to');

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

        // CODE LEVEL
        $cont = $setup->addContainer($this->_('Codes'));
        $cont->addBrowsePage($this->_('Reception codes'), 'pr.reception', 'reception');
        $cont->addBrowsePage($this->_('Consents'), 'pr.consent', 'consent');

        // ACCESS LEVEL
        $cont = $setup->addContainer($this->_('Access'));
        // ROLES CONTROLLER
        $page = $cont->addBrowsePage($this->_('Roles'), 'pr.role', 'role');
        $page->addAction($this->_('Assigned'),   null, 'overview');
        $page->addAction($this->_('Privileges'), null, 'privilege');
        // GROUPS CONTROLLER
        $cont->addBrowsePage($this->_('Groups'), 'pr.group', 'group');
        // ORGANIZATIONS CONTROLLER
        $cont->addBrowsePage($this->_('Organizations'),'pr.organization', 'organization');
        // STAFF CONTROLLER
        $page = $cont->addStaffPage($this->_('Staff'));

        // MAIL CONTAINER
        $setup->addMailSetupMenu($this->_('Mail'));


        // LOG CONTROLLER
        $page = $setup->addPage($this->_('Logging'), 'pr.log', 'log', 'index');
        $page->addAutofilterAction();
        $page->addExcelAction();
        $page->addShowAction();
        $logMaint = $page->addPage($this->_('Maintenance'), 'pr.log.maintenance', 'log-maintenance');
        $logMaint->addAutofilterAction();
        $logMaint->addEditAction('pr.log.maintenance');

        // OpenRosa
        $this->addOpenRosaContainer($this->_('OpenRosa'), $setup);

        return $setup;
    }

    /**
     * Use this to add a privilege that is not associated with a menu item.
     *
     * @param string $privilege
     * @return Gems_Menu
     */
    public function addHiddenPrivilege($privilege)
    {
        $this->_hiddenPrivileges[$privilege] = sprintf($this->_('Stand-alone privilege: %s'), $privilege);

        return $this;
    }

    /**
     * Method retained to maintain BC - {@see Gems_Menu::addHiddenPrivilege}
     *
     * @deprecated
     * @param string $privilege
     * @return Gems_Menu
     */
    public function addHiddenPrivilige($privilege)
    {
        return $this->addHiddenPrivilege($privilege);
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

    /**
     * Shortcut function to add all items needed for OpenRosa
     *
     * Should be enabled in application.ini by using useOpenRosa = 1
     *
     * @param string $label Label for the container
     */
    public function addOpenRosaContainer($label, $parent = null)
    {
        if ($this->escort->getOption('useOpenRosa')) {
            if (is_null($parent)) {
                $parent = $this;
            }
            $page = $parent->addBrowsePage($label, 'pr.openrosa','openrosa');
            $page->addButtonOnly($this->_('Scan Responses'), 'pr.openrosa.scan', 'openrosa', 'scanresponses');
            $this->addPage(null, null, 'openrosa', 'submission');
            $this->addPage(null, null, 'openrosa', 'formList'); //mind the capital L here
            $this->addPage(null, null, 'openrosa', 'download');
            $this->addPage(null, null, 'openrosa', 'barcode'); // For barcode rendering
        }
    }

    /**
     * Shortcut function to create the respondent page.
     *
     * @param string $label Label for the container
     * @return Gems_Menu_MenuAbstract The new respondent page
     */
    public function addRespondentPage($label)
    {
        $orgId = $this->escort->getLoader()->getCurrentUser()->getCurrentOrganizationId();

        $params = array(MUtil_Model::REQUEST_ID1  => 'gr2o_patient_nr', MUtil_Model::REQUEST_ID2 => 'gr2o_id_organization');

        // MAIN RESPONDENTS ITEM
        $page = $this->addPage($label, 'pr.respondent', 'respondent');
        $page->addAutofilterAction();
        $page->addCreateAction('pr.respondent.create')->setParameterFilter('can_add_respondents', true);;
        $page->addShowAction()
                ->setNamedParameters($params)
                ->setHiddenOrgId($orgId);

        /*
        MUtil_Lazy::iff(
            is('gr2o_id_organization', $this->escort->getCurrentOrganization()),
            aget(MUtil_Model::REQUEST_ID,  'gr2o_patient_nr'),
            aget(MUtil_Model::REQUEST_ID1, 'gr2o_patient_nr', MUtil_Model::REQUEST_ID2, 'gr2o_id_organization')
            );
        */

        $page->addEditAction('pr.respondent.edit')
                ->setNamedParameters($params)
                ->setHiddenOrgId($orgId);

        if ($this->escort instanceof Gems_Project_Tracks_SingleTrackInterface) {

            $trType = 'T';
            $subPage = $page->addPage($this->_('Track'), 'pr.track', 'track', 'show-track')
                    ->setNamedParameters($params)
                    ->setHiddenOrgId($orgId)
                    ->addHiddenParameter(Gems_Model::TRACK_ID, $this->escort->getTrackId(), 'gtr_track_type', $trType);

            $tkPages[$trType] = $subPage->addAction($this->_('Token'), 'pr.token', 'show')
                    ->addNamedParameters(MUtil_Model::REQUEST_ID, 'gto_id_token')
                    ->setParameterFilter('gtr_track_type', $trType, Gems_Model::ID_TYPE, 'token');
            $subPage->addAction($this->_('Add'), 'pr.track.create', 'create')
                    ->setNamedParameters($params)
                    ->addNamedParameters(Gems_Model::TRACK_ID, 'gtr_id_track')
                    ->setHiddenOrgId($orgId)
                    ->setParameterFilter('gtr_track_type', $trType, 'track_can_be_created', 1)
                    ->addHiddenParameter('track_can_be_created', 1);
            $subPage->addAction($this->_('Preview'), 'pr.track', 'view')
                    ->setNamedParameters($params)
                    ->addNamedParameters(Gems_Model::TRACK_ID, 'gtr_id_track')
                    ->setHiddenOrgId($orgId)
                    ->setParameterFilter('gtr_track_type', $trType, 'track_can_be_created', 1)
                    ->addHiddenParameter('track_can_be_created', 1);

        } else {

            $trPage = $page->addPage($this->_('Tracks'), 'pr.track', 'track');
            $trType = 'T';

            $trPage->setNamedParameters($params)
                    ->setHiddenOrgId($orgId)
                    ->addAutofilterAction();

            /*
             MUtil_Lazy::iff(is('gtr_track_type', $trType), aget(MUtil_Model::REQUEST_ID, 'gr2o_patient_nr', Gems_Model::TRACK_ID, 'gtr_id_track'))
             */
            $trPage->addAction($this->_('Add'), 'pr.track.create', 'create')
                    ->setNamedParameters($params)
                    ->addNamedParameters(Gems_Model::TRACK_ID, 'gtr_id_track')
                    ->setHiddenOrgId($orgId)
                    ->setParameterFilter('gtr_track_type', $trType);

            $trPage->addAction($this->_('Assignments'), 'pr.track', 'view')
                    ->setNamedParameters($params)
                    ->addNamedParameters(Gems_Model::TRACK_ID, 'gtr_id_track')
                    ->setHiddenOrgId($orgId)
                    ->setParameterFilter('gtr_track_type', $trType);

            $tkPages[$trType] = $trPage->addAction($this->_('Show'), 'pr.track', 'show-track')
                    ->setNamedParameters($params)
                    ->addNamedParameters(Gems_Model::RESPONDENT_TRACK, 'gr2t_id_respondent_track')
                    ->setHiddenOrgId($orgId)
                    ->setParameterFilter('gtr_track_type', $trType);

            $tkPages[$trType]->addAction($this->_('Export track'), 'pr.track', 'export-track')
                    ->setNamedParameters($params)
                    ->addNamedParameters(Gems_Model::RESPONDENT_TRACK, 'gr2t_id_respondent_track')
                    ->setHiddenOrgId($orgId)
                    ->setParameterFilter('gtr_track_type', $trType);

            $tkPages[$trType]->addAction($this->_('Token'), 'pr.token', 'show')
                    ->setNamedParameters(MUtil_Model::REQUEST_ID, 'gto_id_token')
                    ->setParameterFilter('gtr_track_type', $trType, Gems_Model::ID_TYPE, 'token');

            $trPage->addAction($this->_('Edit'), 'pr.track.edit', 'edit-track')
                    ->setNamedParameters($params)
                    ->addNamedParameters(Gems_Model::RESPONDENT_TRACK, 'gr2t_id_respondent_track')
                    ->setHiddenOrgId($orgId)
                    ->setParameterFilter('gtr_track_type', $trType, 'can_edit', 1);

            $trPage->addAction($this->_('Delete'), 'pr.track.delete', 'delete-track')
                    ->setNamedParameters($params)
                    ->addNamedParameters(Gems_Model::RESPONDENT_TRACK, 'gr2t_id_respondent_track')
                    ->setHiddenOrgId($orgId)
                    ->setParameterFilter('gtr_track_type', $trType, 'can_edit', 1);

            if ($this->escort instanceof Gems_Project_Tracks_StandAloneSurveysInterface) {
                $trPage = $page->addPage($this->_('Surveys'), 'pr.survey', 'survey');
                $trType = 'S';

                $trPage->setNamedParameters($params)
                        ->setHiddenOrgId($orgId)
                        ->addAutofilterAction();

                $trPage->addAction($this->_('Add'), 'pr.survey.create', 'create')
                    ->setNamedParameters($params)
                    ->addNamedParameters(Gems_Model::TRACK_ID, 'gtr_id_track')
                    ->setHiddenOrgId($orgId)
                    ->setParameterFilter('gtr_track_type', $trType);

                $trPage->addAction($this->_('Assigned'), 'pr.survey', 'view')
                    ->setNamedParameters($params)
                    ->addNamedParameters(Gems_Model::TRACK_ID, 'gtr_id_track')
                    ->setHiddenOrgId($orgId)
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
                    ->setParameterFilter('can_be_taken', 1, Gems_Model::ID_TYPE, 'token');
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

        $page->addAction($this->_('Export archive'), 'pr.respondent.export-html', 'export')
                ->setNamedParameters($params)
                ->setHiddenOrgId($orgId);

        $page->addDeleteAction('pr.respondent.delete')
                ->setNamedParameters($params)
                ->setHiddenOrgId($orgId);

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

    /**
     * Find a menu item through specifying the controller and action
     *
     * @param string $controller
     * @param string $action
     * @return Gems_SubMenuItem
     */
    public function findController($controller, $action = 'index')
    {
        return $this->findItem(array('controller' => $controller, 'action' => $action), false);
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
     * Menulist populated with current items
     *
     * @param Zend_Controller_Request_Abstract $request
     * @param $parentLabel
     * @return Gems_Menu_MenuList
     */
    public function getCurrentMenuList(Zend_Controller_Request_Abstract $request, $parentLabel = null)
    {
        $controller = $request->getControllerName();
        $action     = $request->getActionName();

        $menuList = $this->getMenuList();

        if ($controller !== 'index') {
            $menuList->addByController($controller, 'index', $parentLabel);
        }

        foreach ($this->getCurrentParent()->getChildren() as $child) {
            if ($child instanceof Gems_Menu_SubMenuItem) {
                $chAction = $child->get('action');
                $chContr  = $child->get('controller');
                if (! ($controller == $chContr && $action == $chAction)) {
                    $menuList->addByController($chContr, $chAction);
                }
            }
        }
        return $menuList;
    }


    /**
     *
     * @return Gems_Menu_SubMenuItem
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
        $privileges = $this->_hiddenPrivileges;

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
        $this->addRespondentPage($this->_('Respondents'));

        /*
        if ($this->escort instanceof Gems_Project_Organization_MultiOrganizationInterface) {
            $this->addPage($this->_('Switch'), 'pr.respondent', 'organization', 'choose');
        } // */

        // MAIN PLANNING ITEM
        $this->addPlanPage($this->_('Overview'));

        // MAIN RESULTS ITEM
        // $menu->addPage($this->_('Results'), 'pr.result', 'result');
        // $menu->addPage($this->_('Invite'), 'pr.invitation', 'invitation');

        // PROJECT INFO
        $this->addProjectPage($this->_('Project'));

        // SETUP CONTAINER
        $this->addGemsSetupContainer($this->_('Setup'));

        // TRACK BUILDER
        $this->addTrackBuilderMenu($this->_('Track Builder'));

        // EXPORT
        $this->addExportContainer($this->_('Export'));

        // IMPORT
        $this->addImportContainer($this->_('Import'));

        // OTHER ITEMS
        $this->addLogonOffToken();

        // CONTACT MENU
        $this->addContactPage($this->_('Contact'));

        // Privileges not associated with menu item
        //$this->addHiddenPrivilege('pr.plan.choose-org');
        $this->addHiddenPrivilege('pr.plan.mail-as-application');
        $this->addHiddenPrivilege('pr.respondent.multiorg');
        $this->addHiddenPrivilege('pr.respondent.result');
        $this->addHiddenPrivilege('pr.respondent.who');
        $this->addHiddenPrivilege('pr.staff.edit.all');
        $this->addHiddenPrivilege('pr.staff.see.all');
        $this->addHiddenPrivilege('pr.token.mail.freetext');


        //Changelog added as button only
        $this->addButtonOnly($this->_('Changelog'),  'pr.project-information.changelog', 'project-information','changelog');

        // Special page for automated e-mail cronjob
        $this->addPage(null, null, 'cron', 'index');
        $this->addPage(null, null, 'email', 'index');
    }

    /**
     * Plug your project menu into this function call
     */
    public function loadProjectMenu()
    {
    }

    /**
     * Renders the element into a html string
     *
     * The $view is used to correctly encode and escape the output
     *
     * @param Zend_View_Abstract $view
     * @return string Correctly encoded and escaped html output
     */
    public function render(Zend_View_Abstract $view)
    {
        if ($this->_onlyActiveBranchVisible) {
            $activePath = $this->_findPath($this->escort->request);

            // MUtil_Echo::r($activePath);

            $this->setBranchVisible($activePath);
        }

        $parameterSources[] = $this->escort->request;

        if ($this->_menuParameters) {
            $parameterSources[] = $this->_menuParameters;
        }

        $source = new Gems_Menu_ParameterCollector($parameterSources);
        // self::$verbose = true;

        $nav = $this->_toNavigationArray($source);

        // MUtil_Echo::track($nav);

        $ul = $this->renderFirst();

        $this->renderItems($ul, $nav);

        return $ul->render($view);
    }

    /**
     * Helper function to create main menu html element.
     *
     * Allows overloading by sub classes.
     *
     * @return MUtil_Html_ListElement
     */
    protected function renderFirst()
    {
        return MUtil_Html::create()->ul(array('class' => 'navigation'));
    }

    /**
     * Helper function to load the menu items to the html element.
     *
     * Allows overloading by sub classes.
     *
     * @param MUtil_Html_ListElement $ul
     * @param array $items
     */
    protected function renderItems(MUtil_Html_ListElement $ul, array $items)
    {
        foreach ($items as $item) {
            if (isset($item['visible'], $item['label']) && $item['visible'] && $item['label']) {
                $url = $item['params'];
                $url['controller'] = $item['controller'];
                $url['action']     = $item['action'];
                $url['RouteReset'] = true;
                $li = $ul->li();
                if (isset($item['active']) && $item['active']) {
                    $li->class = 'active';
                }
                if (isset($item['liClass']) && $item['liClass']) {
                    $li->appendAttrib('class', $item['liClass']);
                }

                $a = $li->a($url, $item['label']);
                if (isset($item['class'])) {
                    $a->class = $item['class'];
                }
                if (isset($item['target'])) {
                    $a->target = $item['target'];
                }

                if (isset($item['pages']) && is_array($item['pages'])) {
                    $this->renderItems($li->ul(), $item['pages']);
                }
            }
        }
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
     * Renders the top level menu items into a html element
     *
     * @return MUtil_Html_HtmlElement
     */
    public function toActiveBranchElement()
    {
        $activePath = $this->_findPath($this->escort->request);

        if (! $activePath) {
            return null;
        }

        $this->setBranchVisible($activePath);

        $activeItem = array_pop($activePath);

        if (! $activeItem instanceof Gems_Menu_SubMenuItem) {
            return null;
        }
        // MUtil_Echo::track($activeItem->get('label'));

        $parameterSources[] = $this->escort->request;

        if ($this->_menuParameters) {
            $parameterSources[] = $this->_menuParameters;
        }

        $source = new Gems_Menu_ParameterCollector($parameterSources);
        // self::$verbose = true;

        $nav = $this->_toNavigationArray($source);

        $ul = $this->renderFirst();

        foreach ($nav as $item => $sub) {
            if ($sub['label'] === $activeItem->get('label')) {

                if (isset($sub['pages'])) {
                    $this->renderItems($ul, $sub['pages']);
                }

                return $ul;
            }
        }

        return null;
    }

    /**
     * Renders the top level menu items into a html element
     *
     * @return MUtil_Html_HtmlElement
     */
    public function toTopLevelElement()
    {

        $this->setBranchVisible(array());
        $activeItems = $this->_findPath($this->escort->request);
        foreach ($activeItems as $activeItem) {
            if ($activeItem instanceof Gems_Menu_SubMenuItem) {
                $activeItem->set('class', 'active');
            }
        }

        $parameterSources[] = $this->escort->request;

        if ($this->_menuParameters) {
            $parameterSources[] = $this->_menuParameters;
        }

        $source = new Gems_Menu_ParameterCollector($parameterSources);

        $nav = $this->_toNavigationArray($source);

        // MUtil_Echo::track($nav);

        $ul = $this->renderFirst();

        $this->renderItems($ul, $nav);

        return $ul;
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
            $parameterSources[] = $this->_menuParameters;
        }

        $source = new Gems_Menu_ParameterCollector($parameterSources);
        // self::$verbose = true;

        $nav = new Zend_Navigation($this->_toNavigationArray($source));

        // MUtil_Echo::track($this->_toNavigationArray($source), $nav);

        return $nav;
    }
}
