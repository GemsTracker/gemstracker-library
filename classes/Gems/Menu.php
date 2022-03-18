<?php

/**
 * @package    Gems
 * @subpackage Menu
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
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
class Gems_Menu extends \Gems_Menu_MenuAbstract implements \MUtil_Html_HtmlInterface
{
    /**
     *
     * @var \Gems_Menu_SubMenuItem
     */
    private $_currentMenuItem;

    private $_hiddenPrivileges = array();

    protected $_menuUlClass = 'navigation nav nav-stacked';

    private $_onlyActiveBranchVisible = false;

    /**
     *
     * @var \Gems_Menu_ParameterSource
     */
    private $_menuParameters;

    private $_visible = true;

    /**
     * @var \Gems\Event\EventDispatcher
     */
    public $event;

    /**
     * Set output echo on for debugging
     *
     * @var boolean
     */
    static public $verbose = false;

    public function  __construct()
    {
        parent::__construct();
    }

    public function afterRegistry()
    {
        parent::afterRegistry();

        //This loads the default menu
        $this->loadDefaultMenu();

        // This is where module menu changes get loaded
        $event = new \Gems\Event\Application\MenuAdd($this);
        $event->setTranslatorAdapter($this->translateAdapter);
        $this->event->dispatch($event, $event::NAME);

        //This is where you plugin your project menu settings
        $this->loadProjectMenu();
        $this->setOnlyActiveBranchVisible();
        $this->applyAcl($this->acl, $this->currentUser->getRole());
    }

    private function _findPath($request)
    {
        $find = $this->request2find($request);

        return $this->findItemPath($find);
    }

    /**
     * Shortcut function to create a ask menu, with hidden options.
     *
     * This function is in \Gems_Menu instead of AbstractMenu because
     * you should ALWAYS put this menu in the root menu.
     *
     * @param string $label Label for the whole menu
     */
    public function addAskPage($label)
    {
        $page = $this->addPage($label, 'pr.respondent.ask', 'ask');
        $page->addAction($this->_('Token lost?'), 'pr.respondent.lost', 'lost');

        // Routes for token controller return
        $this->addPage(null, true, 'ask', 'forward');
        $this->addPage(null, true, 'ask', 'resume-later');
        $this->addPage(null, true, 'ask', 'return');
        $this->addPage(null, true, 'ask', 'to-survey')->setModelParameters(1);
        $this->addPage(null, true, 'ask', 'token');

        return $page;
    }

    /**
     * Shortcut function to create a contact container.
     *
     * This function is in \Gems_Menu instead of AbstractMenu because
     * you should ALWAYS put this menu in the root menu.
     *
     * @param string $label Label for the whole menu
     * @return \Gems_Menu_MenuAbstract The new contact page
     */
    public function addContactPage($label)
    {
        $project = $this->project;

        $page = $this->addPage($label, 'pr.contact', 'contact');

        $page->addAction(sprintf($this->_('About %s'), $project->getName()), 'pr.contact', 'about');
        $page->addAction(sprintf($this->_('About %s'), $this->_('GemsTracker')), 'pr.contact.gems', 'gems');

        $page->addAction($this->_('Reporting bugs'), 'pr.contact.bugs', 'bugs');

        $page->addAction($this->_('Support'), 'pr.contact.support', 'support');

        return $page;
    }

    /**
     * Shortcut function to create a setup container.
     *
     * This function is in \Gems_Menu instead of AbstractMenu because
     * you should ALWAYS put this menu in the root menu.
     *
     * @param string $label Label for the whole menu
     * @return \Gems_Menu_ContainerItem
     */
    public function addGemsSetupContainer($label)
    {
        $setup = $this->addContainer($label);

        // PROJECT LEVEL
        $cont = $setup->addProjectInfoPage($this->_('Project setup'));

        // DATABASE CONTROLLER
        $page = $setup->addPage($this->_('Database'), 'pr.database', 'database');
        $page->addAutofilterAction();
        // Creation not possible
        $showPage = $page->addShowAction('pr.database');
        $showPage->addAction($this->_('Content'), 'pr.database', 'view')
            ->addParameters(\MUtil_Model::REQUEST_ID)
            ->setParameterFilter('exists', true);
        $showPage->addAction($this->_('Execute'), 'pr.database.create', 'run')
            ->addParameters(\MUtil_Model::REQUEST_ID)
            ->setParameterFilter('script', true);
        $showPage->addDeleteAction('pr.database.delete')
            ->setParameterFilter('exists', true);
        $page->addAction($this->_('Execute new'), 'pr.database.create', 'run-all');
        $page->addAction($this->_('Patches'), 'pr.database.patches', 'patch');
        $page->addAction($this->_('Show structure changes'), 'pr.database', 'show-changes');
        if (isset($this->project->databaseTranslations)) {
            $page->addAction($this->_('Refresh translateables'), 'pr.database', 'refresh-translations');
        }
        $page->addAction($this->_('Run SQL'), 'pr.database.execute', 'run-sql');

        $databaseBackup = $page->addPage($this->_('Database backup'), 'pr.database.backup', 'database-backup', 'index');
        $databaseBackup->addActionButton($this->_('Backup'), 'pr.database.backup', 'backup');
        $databaseBackup->addAutofilterAction();

        // CODE LEVEL
        $cont = $setup->addContainer($this->_('Codes'));
        $cont->addBrowsePage($this->_('Reception codes'), 'pr.reception', 'reception');
        $cont->addBrowsePage($this->_('Consents'), 'pr.consent', 'consent');
        $cont->addBrowsePage($this->_('Mail codes'), 'pr.mailcode', 'mail-code');

        // ACCESS LEVEL
        $cont = $setup->addContainer($this->_('Access'));
        // ROLES CONTROLLER
        $page = $cont->addBrowsePage($this->_('Roles'), 'pr.role', 'role');

        // ASSIGNED CONTROLLER
        $assiPage = $page->addPage($this->_('Assigned'), 'pr.role', 'role-overview', 'index');
        $assiPage->addAutofilterAction();
        $assiPage->addExportAction();

        // PRIVILEGES CONTROLLER
        $privPage = $page->addPage($this->_('Privileges'), 'pr.role', 'privileges', 'index');
        $privPage->addAutofilterAction();
        $privPage->addExportAction();

        // GROUPS CONTROLLER
        $cont->addGroupsPage($this->_('Groups'));
        // ORGANIZATIONS CONTROLLER
        $orgsPage = $cont->addBrowsePage($this->_('Organizations'), 'pr.organization', 'organization');
        $orgsPage->addAction($this->_('Check all respondents'), 'pr.organization.check-all', 'check-all');
        $orgPage = $this->findController('organization', 'show');
        $orgPage->addAction($this->_('Check respondents'), 'pr.organization.check-org', 'check-org')
                ->setModelParameters(1);

        // STAFF CONTROLLER
        $cont->addStaffPage($this->_('Staff'));

        // SYSTEM USER CONTROLLER
        $cont->addSystemUserPage($this->_('System users'));

        // SITE MAINTENANCE CONTROLLER
        $sitesPage = $cont->addBrowsePage($this->_('Sites'), 'pr.site-maint', 'site-maintenance');
        $sitesPage->addButtonOnly($this->_('BLOCK new sites'), 'pr.site-maint.lock', 'site-maintenance', 'lock');
        
        // AGENDA CONTAINER
        $setup->addAgendaSetupMenu($this->_('Agenda'));

        // MAIL CONTAINER
        $setup->addCommSetupMenu($this->_('Communication'));

        // LOG CONTROLLER
        $setup->addLogControllers();

        // OpenRosa
        $this->addOpenRosaContainer($this->_('OpenRosa'), $setup);

        return $setup;
    }

    /**
     * Use this to add a privilege that is not associated with a menu item.
     *
     * @param string $privilege
     * @param string $label
     * @return \Gems_Menu
     */
    public function addHiddenPrivilege($privilege, $label = null)
    {
        if (null === $label) {
            $label = $this->_('Stand-alone privilege: %s');
        }
        $this->_hiddenPrivileges[$privilege] = sprintf($label, $privilege);

        return $this;
    }

    /**
     * Use this to add all and not-displayed privileges
     *
     * @return \void
     */
    public function addHiddenPrivileges()
    {
        // Privileges not associated with menu item
        $this->addHiddenPrivilege('pr.organization-switch', $this->_(
                'Grant access to all organization.'
                ));
        $this->addHiddenPrivilege('pr.plan.mail-as-application', $this->_(
                'Grant right to impersonate the site when mailing.'
                ));
        $this->addHiddenPrivilege('pr.respondent.multiorg', $this->_(
                'Display multiple organizations in respondent overview.'
                ));
        $this->addHiddenPrivilege('pr.episodes.rawdata', $this->_(
                'Display raw data in Episodes of Care.'
                ));
        $this->addHiddenPrivilege('pr.respondent.result', $this->_(
                'Display results in token overviews.'
                ));
        $this->addHiddenPrivilege('pr.respondent.select-on-track', $this->_(
                'Grant checkboxes to select respondents on track status in respondent overview.'
                ));
        $this->addHiddenPrivilege('pr.respondent.show-deleted', $this->_(
                'Grant checkbox to view deleted respondents in respondent overview.'
                ));
        $this->addHiddenPrivilege('pr.respondent.who', $this->_(
                'Display staff member name in token overviews.'
                ));
        $this->addHiddenPrivilege('pr.staff.edit.all', $this->_(
                'Grant right to edit staff members from all organizations.'
                ));
        $this->addHiddenPrivilege('pr.export.add-resp-nr', $this->_(
                'Grant right to export respondent numbers with survey answers.'
                ));
        $this->addHiddenPrivilege('pr.export.gender-age', $this->_(
                'Grant right to export gender and age information with survey answers.'
                ));
        $this->addHiddenPrivilege('pr.staff.see.all', $this->_(
                'Display all organizations in staff overview.'
                ));
        $this->addHiddenPrivilege('pr.group.switch', $this->_(
                'Grant right to switch groups.'
                ));
        $this->addHiddenPrivilege('pr.token.mail.freetext', $this->_(
                'Grant right to send free text (i.e. non-template) email messages.'
                ));
        $this->addHiddenPrivilege('pr.systemuser.seepwd', $this->_(
                'Grant right to see password of system users (without editing right).'
                ));


        $this->addPage(null, 'pr.cron.job', 'cron', 'index');
        $this->addPage(null, 'pr.cron.job', 'cron', 'monitor');
        $this->addPage(null, 'pr.cron.job', 'cron', 'test');

        $this->addPage(null, 'pr.embed.login', 'embed', 'login');
        $this->addHiddenPrivilege('pr.embed.login', $this->_(
                'Grant right for access to embedded login page.'
                ));

        $this->addPage(null, null, 'email', 'index');
    }

    public function addLogonOffToken()
    {
        $this->addPage($this->_('Login'), 'pr.nologin', 'index', 'login')
             ->addAction($this->_('Lost password'), 'pr.nologin', 'resetpassword', ['allowed-alternative' => 'option/change-password']);

        $optionPage = $this->addPage($this->_('Your account'), 'pr.option.edit', 'option', 'edit');
        $logPage = $optionPage->addAction($this->_('Activity overview'), 'pr.option.edit', 'overview');
        $logPage->addAction($this->_('Show'), 'pr.option.edit', 'show-log')
                ->setNamedParameters(\Gems_Model::LOG_ITEM_ID, 'gla_id');
        $optionPage->addAction($this->_('Change password'), 'pr.option.password', 'change-password');
        $optionPage->addAction($this->_('Two factor setup'), 'pr.option.2factor', 'two-factor');

        $this->addParticipatePage($this->_('Participate'));
        $this->addAskPage($this->_('Token'));
        $this->addPage($this->_('Logoff'), 'pr.islogin', 'index', 'logoff');

        if (count($this->currentUser->getAllowedStaffGroups(false)) > 1) {
            // ALLOW GROUP SWITCH
            $this->addPage(null, null, 'group', 'change-ui');
        }

        if ($this->project->multiLocale) {
            // ALLOW LANGUAGE CHANGE
            $this->addPage(null, null, 'language', 'change-ui');
        }

        if (count($this->currentUser->getAllowedOrganizations()) > 1) {
            // ALLOW ORGANIZATION CHANGE
            $this->addPage(null, null, 'organization', 'change-ui');
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
        if ($this->escort instanceof \GemsEscort && $this->escort->getOption('useOpenRosa')) {
            if (is_null($parent)) {
                $parent = $this;
            }
            $page = $parent->addBrowsePage($label, 'pr.openrosa','openrosa');
            $page->addButtonOnly($this->_('Scan Responses'), 'pr.openrosa.scan', 'openrosa', 'scanresponses');
            $this->addPage(null, null, 'openrosa', 'submission');
            $this->addPage(null, null, 'openrosa', 'formList'); //mind the capital L here
            $this->addPage(null, null, 'openrosa', 'download');
            $this->addPage(null, null, 'openrosa', 'barcode'); // For barcode rendering
            $this->addPage(null, 'pr.islogin', 'openrosa', 'image'); // For image rendering

            $this->addPage(null, null, 'open-rosa-form', 'edit');
        }
    }

    /**
     * Shortcut function to create a participate page
     *
     * This function is in \Gems_Menu instead of AbstractMenu because
     * you should ALWAYS put this menu in the root menu.
     *
     * @param string $label Label for the whole menu
     */
    public function addParticipatePage($label)
    {
        $participate = $this->addContainer($label);
        $subscr = $participate->addPage($this->_('Subscribe'), 'pr.participate.subscribe', 'participate', 'subscribe');
        $subscr->addPage(null, 'pr.participate.subscribe', 'participate', 'subscribe-thanks');
        $unsub = $participate->addPage($this->_('Unsubscribe'), 'pr.participate.unsubscribe', 'participate', 'unsubscribe');
        $unsub->addPage(null, 'pr.participate.unsubscribe', 'participate', 'unsubscribe-thanks');

        return $participate;
    }

    /**
     * Shortcut function to create the respondent page.
     *
     * @param string $label Label for the container
     * @return \Gems_Menu_MenuAbstract The new respondent page
     */
    public function addRespondentPage($label)
    {
        $orgId = $this->currentUser->getCurrentOrganizationId();

        $params = array(\MUtil_Model::REQUEST_ID1  => 'gr2o_patient_nr', \MUtil_Model::REQUEST_ID2 => 'gr2o_id_organization');

        // MAIN RESPONDENTS ITEM
        $page = $this->addPage($label, 'pr.respondent', 'respondent');
        $page->addAutofilterAction();
        $page->addCreateAction('pr.respondent.create')->setParameterFilter('can_add_respondents', true);
        $page->addExportAction();
        $page->addImportAction();
        $page->addAction(null, 'pr.respondent.simple-api', 'simple-api');

        $rPage = $page->addShowAction()
                ->setNamedParameters($params)
                ->setHiddenOrgId($orgId);

        $rPage->addEditAction('pr.respondent.edit')
                ->setNamedParameters($params)
                ->setHiddenOrgId($orgId);
        $rPage->addAction($this->_('Consent'), 'pr.respondent.change-consent', 'change-consent')
                ->setNamedParameters($params)
                ->setHiddenOrgId($orgId);
        $rPage->addAction($this->_('Change organization'), 'pr.respondent.change-org', 'change-organization')
                ->setNamedParameters($params)
                ->setHiddenOrgId($orgId);

        $rPage->addAction(null, 'pr.token.answers', 'overview')
                ->setNamedParameters($params)
                ->setHiddenOrgId($orgId);

        $rPage->addPage($this->_('View survey'), 'pr.track.insert', 'track', 'view-survey', array('button_only' => true))
                    ->setNamedParameters($params)
                    ->addNamedParameters(\Gems_Model::SURVEY_ID, 'gsu_id_survey')
                    ->setHiddenOrgId($orgId);

        // Add "episodes of care"
        $epiParams = array(\Gems_Model::EPISODE_ID => 'gec_episode_of_care_id'); // + $params;
        $epage = $rPage->addPage($this->_('Episodes'), 'pr.episodes', 'care-episode');
        $epage->setNamedParameters($params)
                ->setHiddenOrgId($orgId);
        $epage->addAutofilterAction();
        $epage->addCreateAction()->setNamedParameters($params)->setHiddenOrgId($orgId);
        $epage = $epage->addShowAction()->setNamedParameters($epiParams);
        $epage->addEditAction()->setNamedParameters($epiParams);
        $epage->addDeleteAction()->setNamedParameters($epiParams);
        // $epage->addAction($this->_('Check'), 'pr.episodes.check', 'check')->setNamedParameters($epiParams);

        // Add "appointments"
        $appParams = array(\Gems_Model::APPOINTMENT_ID => 'gap_id_appointment'); // + $params;
        $apage = $rPage->addPage($this->_('Appointments'), 'pr.appointments', 'appointment');
        $apage->setNamedParameters($params)
                ->setHiddenOrgId($orgId);
        $apage->addAutofilterAction();
        $apage->addCreateAction()->setNamedParameters($params)->setHiddenOrgId($orgId);
        $apage = $apage->addShowAction()->setNamedParameters($appParams);
        $apage->addEditAction()->setNamedParameters($appParams);
        $apage->addDeleteAction()->setNamedParameters($appParams);
        $apage->addAction($this->_('Filter Check'), 'pr.appointments.check', 'check')->setNamedParameters($appParams);

        if ($this->escort instanceof \Gems_Project_Tracks_SingleTrackInterface) {

            $subPage = $rPage->addPage($this->_('Track'), 'pr.track', 'track', 'show-track')
                    ->setNamedParameters($params)
                    ->setHiddenOrgId($orgId)
                    ->addHiddenParameter(\Gems_Model::TRACK_ID, $this->escort->getTrackId());

            $subPage->addAction($this->_('Add'), 'pr.track.create', 'create')
                    ->setNamedParameters($params)
                    ->addNamedParameters(\Gems_Model::TRACK_ID, 'gtr_id_track')
                    ->setHiddenOrgId($orgId)
                    ->setParameterFilter('track_can_be_created', 1)
                    ->addHiddenParameter('track_can_be_created', 1);
            $subPage->addAction($this->_('Preview'), 'pr.track', 'view')
                    ->setNamedParameters($params)
                    ->addNamedParameters(\Gems_Model::TRACK_ID, 'gtr_id_track')
                    ->setHiddenOrgId($orgId)
                    ->setParameterFilter('track_can_be_created', 1)
                    ->addHiddenParameter('track_can_be_created', 1);
            $subPage->addAction($this->_('Edit'), 'pr.track.edit', 'edit-track')
                    ->setNamedParameters($params)
                    ->addNamedParameters(\Gems_Model::TRACK_ID, 'gtr_id_track')
                    ->setHiddenOrgId($orgId)
                    ->setParameterFilter('track_can_be_created', 0)
                    ->addHiddenParameter('track_can_be_created', 0);

            $tkPage = $subPage->addAction($this->_('Token'), 'pr.token', 'show')
                    ->addNamedParameters(\MUtil_Model::REQUEST_ID, 'gto_id_token')
                    ->setParameterFilter(\Gems_Model::ID_TYPE, 'token');

            $subPage->addAction($this->_('Insert'), 'pr.track.insert', 'insert')
                    ->setNamedParameters($params)
                    ->addOptionalParameters(\Gems_Model::SURVEY_ID, 'gsu_id_survey')
                    ->setHiddenOrgId($orgId);

            $subPage->addAction($this->_('Check answers'), 'pr.track.answers', 'check-track-answers')
                    ->setNamedParameters($params)
                    ->addNamedParameters(\Gems_Model::TRACK_ID, 'gtr_id_track')
                    ->setHiddenOrgId($orgId)
                    ->setParameterFilter('track_can_be_created', 0)
                    ->addHiddenParameter('track_can_be_created', 0);
            $subPage->addAction($this->_('Check rounds'), 'pr.track.check', 'check-track')
                    ->setNamedParameters($params)
                    ->addNamedParameters(\Gems_Model::TRACK_ID, 'gtr_id_track')
                    ->setHiddenOrgId($orgId)
                    ->setParameterFilter('track_can_be_created', 0, 'gtr_active', 1, 'gr2t_active', 1)
                    ->addHiddenParameter('track_can_be_created', 0);
            $subPage->addAction($this->_('Recalculate fields'), 'pr.track.check', 'recalc-fields')
                    ->setNamedParameters($params)
                    ->addNamedParameters(\Gems_Model::TRACK_ID, 'gtr_id_track')
                    ->setHiddenOrgId($orgId)
                    ->setParameterFilter('track_can_be_created', 0, 'gtr_active', 1, 'gr2t_active', 1)
                    ->addHiddenParameter('track_can_be_created', 0);

        } else {

            $trPage = $rPage->addPage($this->_('Tracks'), 'pr.track', 'track');
            $trPage->setNamedParameters($params)
                    ->setHiddenOrgId($orgId)
                    ->addAutofilterAction();

            $trPage->addAction($this->_('Add'), 'pr.track.create', 'create')
                    ->setNamedParameters($params)
                    ->addNamedParameters(\Gems_Model::TRACK_ID, 'gtr_id_track')
                    ->setHiddenOrgId($orgId);

            $trPage->addAction($this->_('Assignments'), 'pr.track', 'view')
                    ->setNamedParameters($params)
                    ->addNamedParameters(\Gems_Model::TRACK_ID, 'gtr_id_track')
                    ->setHiddenOrgId($orgId);

            $trPage->addAction($this->_('Insert'), 'pr.track.insert', 'insert')
                    ->setNamedParameters($params)
                    ->addOptionalParameters(\Gems_Model::SURVEY_ID, 'gsu_id_survey')
                    ->setHiddenOrgId($orgId);

            $itemPage = $trPage->addAction($this->_('Show track'), 'pr.track', 'show-track')
                    ->setNamedParameters($params)
                    ->addNamedParameters(\Gems_Model::RESPONDENT_TRACK, 'gr2t_id_respondent_track')
                    ->setHiddenOrgId($orgId);

            $itemPage->addAction($this->_('Edit'), 'pr.track.edit', 'edit-track')
                    ->setNamedParameters($params)
                    ->addNamedParameters(\Gems_Model::RESPONDENT_TRACK, 'gr2t_id_respondent_track')
                    ->setHiddenOrgId($orgId)
                    ->setParameterFilter('can_edit', 1);

            $itemPage->addAction($this->_('Delete'), 'pr.track.delete', 'delete-track')
                    ->setNamedParameters($params)
                    ->addNamedParameters(\Gems_Model::RESPONDENT_TRACK, 'gr2t_id_respondent_track')
                    ->setHiddenOrgId($orgId)
                    ->setParameterFilter('can_edit', 1);

            $itemPage->addAction($this->_('Undelete!'), 'pr.track.undelete', 'undelete-track')
                    ->setNamedParameters($params)
                    ->addNamedParameters(\Gems_Model::RESPONDENT_TRACK, 'gr2t_id_respondent_track')
                    ->setHiddenOrgId($orgId)
                    ->setParameterFilter('can_edit', 0);

            $itemPage->addAction($this->_('Check answers'), 'pr.track.answers', 'check-track-answers')
                    ->setNamedParameters($params)
                    ->addNamedParameters(\Gems_Model::RESPONDENT_TRACK, 'gr2t_id_respondent_track')
                    ->setHiddenOrgId($orgId);
            $itemPage->addAction($this->_('Check rounds'), 'pr.track.check', 'check-track')
                    ->setNamedParameters($params)
                    ->addNamedParameters(\Gems_Model::RESPONDENT_TRACK, 'gr2t_id_respondent_track')
                    ->setHiddenOrgId($orgId)
                    ->setParameterFilter('can_edit', 1, 'gtr_active', 1, 'gr2t_active', 1);
            $itemPage->addAction($this->_('Recalculate fields'), 'pr.track.check', 'recalc-fields')
                    ->setNamedParameters($params)
                    ->addNamedParameters(\Gems_Model::RESPONDENT_TRACK, 'gr2t_id_respondent_track')
                    ->setHiddenOrgId($orgId)
                    ->setParameterFilter('can_edit', 1, 'gtr_active', 1, 'gr2t_active', 1);

            $itemPage->addAction($this->_('Export track'), 'pr.track', 'export-track')
                    ->setNamedParameters($params)
                    ->addNamedParameters(\Gems_Model::RESPONDENT_TRACK, 'gr2t_id_respondent_track')
                    ->setHiddenOrgId($orgId)
                    ->setParameterFilter('can_edit', 1);

            $tkPage = $itemPage->addAction($this->_('Token'), 'pr.token', 'show')
                    ->setNamedParameters(\MUtil_Model::REQUEST_ID, 'gto_id_token')
                    ->setParameterFilter(\Gems_Model::ID_TYPE, 'token');

            $trPage->addAction($this->_('Check all answers'), 'pr.track.answers', 'check-all-answers')
                    ->setNamedParameters($params)
                    ->setHiddenOrgId($orgId);
            $trPage->addAction($this->_('Check all rounds'), 'pr.track.check', 'check-all-tracks')
                    ->setNamedParameters($params)
                    ->setHiddenOrgId($orgId);
            $trPage->addAction($this->_('Recalculate all fields'), 'pr.track.check', 'recalc-all-fields')
                    ->setNamedParameters($params)
                    ->setHiddenOrgId($orgId);

            $trPage = $rPage->addPage($this->_('Surveys'), 'pr.survey', 'token');

            // Surveys overview
            $trPage->setNamedParameters($params)
                    ->setHiddenOrgId($orgId)
                    ->addAutofilterAction();
        }

        $tkPage->addEditAction('pr.token.edit')
                ->setNamedParameters(\MUtil_Model::REQUEST_ID, 'gto_id_token')
                ->setParameterFilter('grc_success', 1, \Gems_Model::ID_TYPE, 'token');

        $tkPage->addAction($this->_('Correct answers'), 'pr.token.correct', 'correct')
                ->addNamedParameters(MUtil_Model::REQUEST_ID, 'gto_id_token')
                ->setParameterFilter('is_completed', 1, 'grc_success', 1, Gems_Model::ID_TYPE, 'token', 'show_answers', 1);
        $tkPage->addDeleteAction('pr.token.delete')
                ->addNamedParameters(\MUtil_Model::REQUEST_ID, 'gto_id_token')
                ->setParameterFilter('grc_success', 1, \Gems_Model::ID_TYPE, 'token');
        $tkPage->addAction($this->_('Undelete!'), 'pr.token.undelete', 'undelete')
                ->addNamedParameters(\MUtil_Model::REQUEST_ID, 'gto_id_token')
                ->setParameterFilter('grc_success', 0, \Gems_Model::ID_TYPE, 'token');

        $tkPage->addButtonOnly($this->_('Fill in'), 'pr.ask', 'ask', 'take')
                ->addNamedParameters(\MUtil_Model::REQUEST_ID, 'gto_id_token')
                ->setParameterFilter('can_be_taken', 1, \Gems_Model::ID_TYPE, 'token');
        $tkPage->addPdfButton($this->_('Print PDF'), 'pr.token.print')
                ->addNamedParameters(\MUtil_Model::REQUEST_ID, 'gto_id_token')
                ->setParameterFilter('gsu_has_pdf', 1, \Gems_Model::ID_TYPE, 'token');
        $tkPage->addAction($this->_('E-Mail now!'), 'pr.token.mail', 'email')
                ->addNamedParameters(\MUtil_Model::REQUEST_ID, 'gto_id_token')
                ->setParameterFilter('can_be_taken', 1, 'can_email', 1, \Gems_Model::ID_TYPE, 'token');
        $tkPage->addAction($this->_('Preview'), 'pr.project.questions', 'questions')
                ->addNamedParameters(\MUtil_Model::REQUEST_ID, 'gto_id_token')
                ->setParameterFilter(\Gems_Model::ID_TYPE, 'token');
        $tkPage->addActionButton($this->_('Answers'), 'pr.token.answers', 'answer', ['class' => $this->currentUser->isSessionFramed() ? 'inline-answers' : ''])
                ->addNamedParameters(\MUtil_Model::REQUEST_ID, 'gto_id_token')
                ->setParameterFilter('gto_in_source', 1, \Gems_Model::ID_TYPE, 'token', 'show_answers', 1)
                ->set('target', \MUtil_Model::REQUEST_ID);
        $tkPage->addAction($this->_('Token check'), 'pr.token.check', 'check-token')
               ->addNamedParameters(\MUtil_Model::REQUEST_ID, 'gto_id_token')
               ->setParameterFilter(\Gems_Model::ID_TYPE, 'token');
        $tkPage->addAction($this->_('(Re)check answers'), 'pr.token.answers', 'check-token-answers')
                ->addNamedParameters(\MUtil_Model::REQUEST_ID, 'gto_id_token')
                ->setParameterFilter(\Gems_Model::ID_TYPE, 'token');
        $tkPage->addActionButton($this->_('Export answers'), 'pr.token.answers', 'answer-export')
                ->addNamedParameters(\MUtil_Model::REQUEST_ID, 'gto_id_token')
                ->setParameterFilter('is_completed', 1, \Gems_Model::ID_TYPE, 'token');


        $rPage->addAction($this->_('Export archive'), 'pr.respondent.export-html', 'export-archive')
                ->setNamedParameters($params)
                ->setHiddenOrgId($orgId);

        $mailLogPage = $rPage->addPage($this->_('Communication log'), 'pr.respondent-commlog', 'respondent-mail-log', 'index')
                ->setNamedParameters($params)
                ->setHiddenOrgId($orgId);
        $mailLogPage->addAutofilterAction();
        $showPage = $mailLogPage->addShowAction();
        $showPage->addButtonOnly($this->_('Resend mail'), 'pr.respondent-commlog.resend', 'respondent-mail-log', 'resend')
                 ->setModelParameters(1);

        // LOG CONTROLLER
        $logPage = $rPage->addPage($this->_('Activity log'), 'pr.respondent-log', 'respondent-log', 'index');
        $logPage->setNamedParameters($params)
                ->setHiddenOrgId($orgId);
        $logPage->addAutofilterAction();
        $logParams = $params + array(\Gems_Model::LOG_ITEM_ID => 'gla_id');
        $logPage->addShowAction()
                ->setNamedParameters($logParams)
                ->setHiddenOrgId($orgId);

        $rPage->addDeleteAction('pr.respondent.delete')
                ->setNamedParameters($params)
                ->setParameterFilter('resp_deleted', 0)
                ->setHiddenOrgId($orgId);
        $rPage->addAction($this->_('Undelete!'), 'pr.respondent.undelete', 'undelete')
                ->setNamedParameters($params)
                ->setParameterFilter('resp_deleted', 1)
                ->setHiddenOrgId($orgId);

        /* Add respondent relations */
        $relParams = $params + array('rid' => 'grr_id');
        $relationsPage = $rPage->addPage($this->_('Relations'), 'pr.respondent.relation', 'respondent-relation', 'index')
                ->setNamedParameters($params)
                ->setHiddenOrgId($orgId);
        $relationsPage->addAutofilterAction();
        $relationsPage->addCreateAction();
        $relationsPage->addEditAction();
        $relationsPage->addDeleteAction();
        foreach ($relationsPage->getChildren() as $relation)
        {
            if (!in_array($relation->get('action'), array('autofilter', 'create'))) {
                $relation->setNamedParameters($relParams)
                         ->setHiddenOrgId($orgId);
            } else {
                $relation->setNamedParameters($params)
                         ->setHiddenOrgId($orgId);
            }
        }

        return $rPage;
    }

    /**
     *
     * @param \Zend_Controller_Request_Abstract|array $request
     * @return \Gems_Menu_SubMenuItem|null
     */
    public function find($request)
    {
        $find = $this->request2find($request);

        return $this->findItem($find, true);
    }

    /**
     * Find a menu item through specifying the controller and action
     *
     * @param string $controller
     * @param string $action
     * @return \Gems_SubMenuItem
     */
    public function findAllowedController($controller, $action = 'index')
    {
        return $this->findItem(array('controller' => $controller, 'action' => $action, 'allowed' => true), false);
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

            return new \MUtil_Html_MultiWrapper($results);
        }
    }

    /**
     * Find a menu item through specifying the controller and action
     *
     * @param string $controller
     * @param string $action
     * @return \Gems_SubMenuItem
     */
    public function findController($controller, $action = 'index')
    {
        return $this->findItem(array('controller' => $controller, 'action' => $action), true);
    }

    public function findFirst($request)
    {
        $find = $this->request2find($request);

        return $this->findItem($find, false);
    }

    public function getActivePath(\Zend_Controller_Request_Abstract $request)
    {
        $activePath = $this->_findPath($request);

        return array_reverse($activePath);
    }

    /**
     *
     * @return \Gems_Menu_SubMenuItem
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
     * @param \Zend_Controller_Request_Abstract $request
     * @param $parentLabel
     * @return \Gems_Menu_MenuList
     */
    public function getCurrentMenuList(\Zend_Controller_Request_Abstract $request, $parentLabel = null)
    {
        $controller = $request->getControllerName();
        $action     = $request->getActionName();

        $menuList = $this->getMenuList();

        if ($controller !== 'index') {
            $menuList->addByController($controller, 'index', $parentLabel);
        }

        foreach ($this->getCurrent()->getChildren() as $child) {
            if ($child instanceof \Gems_Menu_SubMenuItem) {
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
     * @return \Gems_Menu_SubMenuItem
     */
    public function getCurrentParent()
    {
        if ($current = $this->getCurrent()) {
            return $current->getParent();
        }
    }

    /**
     *
     * @return \Gems_Menu_MenuList
     */
    public function getMenuList()
    {
        return new \Gems_Menu_MenuList($this);
    }

    /**
     * Use to set parameters that will be used when
     * drawing the navigation menu.
     *
     * @return \Gems_Menu_ParameterSource
     */
    public function getParameterSource()
    {
        if (! $this->_menuParameters) {
            $this->_menuParameters = new \Gems_Menu_ParameterSource();
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

        // MAIN CALENDAR ITEM
        $this->addCalendarPage($this->_('Calendar'));

        // MAIN PLANNING ITEM
        $this->addPlanPage($this->_('Overview'));

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

        // HIDDEN PRIVILEGES
        $this->addHiddenPrivileges();
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
     * @param \Zend_View_Abstract $view
     * @return string Correctly encoded and escaped html output
     */
    public function render(\Zend_View_Abstract $view)
    {
        $request = $this->_getOriginalRequest();

        if ($this->_onlyActiveBranchVisible) {
            $activePath = $this->_findPath($request);

            // \MUtil_Echo::r($activePath);

            $this->setBranchVisible($activePath);
        }

        $parameterSources[] = $request;

        if ($this->_menuParameters) {
            $parameterSources[] = $this->_menuParameters;
        }

        $source = new \Gems_Menu_ParameterCollector($parameterSources);
        // self::$verbose = true;

        $nav = $this->_toNavigationArray($source);

        // \MUtil_Echo::track($nav);

        $ul = $this->renderFirst();

        $this->renderItems($ul, $nav, true);

        return $ul->render($view);
    }

    /**
     * Helper function to create main menu html element.
     *
     * Allows overloading by sub classes.
     *
     * @return \MUtil_Html_ListElement
     */
    protected function renderFirst()
    {
        return \MUtil_Html::create()->ul(array('class' => $this->_menuUlClass));
    }

    /**
     * Helper function to load the menu items to the html element.
     *
     * Allows overloading by sub classes.
     *
     * @param \MUtil_Html_ListElement $ul
     * @param array $items
     * @param boolean $cascade render nested items
     */
    protected function renderItems(\MUtil_Html_ListElement $ul, array $items, $cascade)
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

                if ($cascade && isset($item['pages']) && is_array($item['pages'])) {
                    $this->renderItems($li->ul(array('class' => 'subnav '. $this->_menuUlClass)), $item['pages'], true);
                }
            }
        }
    }

    protected function request2find($request)
    {
        if (is_array($request)) {
            return $request;
        }

        if ($request instanceof \Zend_Controller_Request_Abstract) {
            $find['action']     = $request->getActionName();
            $find['controller'] = $request->getControllerName();
        } else {
            throw new \Gems_Exception_Coding('Not a valid menu search request!');
        }
        // \MUtil_Echo::r($find);

        return $find;
    }

    public function setCurrent(\Gems_Menu_SubMenuItem $item)
    {
        if ('autofilter' == $item->get('action')) {
            $this->_currentMenuItem = $item->getParent();
        } else {
            $this->_currentMenuItem = $item;
        }
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
     * @return \MUtil_Html_HtmlElement
     */
    public function toActiveBranchElement()
    {
        $request    = $this->_getOriginalRequest();
        $activePath = $this->_findPath($request);

        if (! $activePath) {
            return null;
        }

        $this->setBranchVisible($activePath);

        $activeItem = array_pop($activePath);

        if (! $activeItem instanceof \Gems_Menu_SubMenuItem) {
            return null;
        }
        // \MUtil_Echo::track($activeItem->get('label'));

        $parameterSources[] = $request;

        if ($this->_menuParameters) {
            $parameterSources[] = $this->_menuParameters;
        }

        $source = new \Gems_Menu_ParameterCollector($parameterSources);
        // self::$verbose = true;

        $nav = $this->_toNavigationArray($source);

        $ul = $this->renderFirst();

        foreach ($nav as $item => $sub) {
            if ($sub['label'] === $activeItem->get('label')) {

                if (isset($sub['pages'])) {
                    $this->renderItems($ul, $sub['pages'], true);
                }

                return $ul;
            }
        }

        return null;
    }

    /**
     * Renders the top level menu items into a html element
     *
     * @return \MUtil_Html_HtmlElement
     */
    public function toTopLevelElement()
    {
        $request     = $this->_getOriginalRequest();
        $activeItems = $this->_findPath($request);
        $this->setBranchVisible($activeItems);
        foreach ($activeItems as $activeItem) {
            if ($activeItem instanceof \Gems_Menu_SubMenuItem) {
                $activeItem->set('class', 'active');
            }
        }

        $parameterSources[] = $request;

        if ($this->_menuParameters) {
            $parameterSources[] = $this->_menuParameters;
        }

        $source = new \Gems_Menu_ParameterCollector($parameterSources);

        $nav = $this->_toNavigationArray($source);

        // \MUtil_Echo::track($nav);

        $ul = $this->renderFirst();

        $this->renderItems($ul, $nav, false);

        return $ul;
    }

    /**
     * Generates a \Zend_Navigation object from the current menu
     *
     * @param \Zend_Controller_Request_Abstract $request
     * @param mixed $actionController
     * @return \Zend_Navigation
     */
    public function toZendNavigation(\Zend_Controller_Request_Abstract $request, $actionController = null)
    {
        if ($this->_onlyActiveBranchVisible) {
            $activePath = $this->_findPath($request);

            // \MUtil_Echo::r($activePath);
            $this->setBranchVisible($activePath);
        }

        $parameterSources = func_get_args();

        if ($this->_menuParameters) {
            $parameterSources[] = $this->_menuParameters;
        }

        $source = new \Gems_Menu_ParameterCollector($parameterSources);
        // self::$verbose = true;

        $nav = new \Zend_Navigation($this->_toNavigationArray($source));

        // \MUtil_Echo::track($this->_toNavigationArray($source), $nav);

        return $nav;
    }
}
