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
 * @subpackage Menu
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Base class for building a menu / button structure where the display of items is dependent
 * on both privileges and the availability of parameter information,
 * e.g. data to fill an 'id' parameter.
 *
 * @package    Gems
 * @subpackage Menu
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
abstract class Gems_Menu_MenuAbstract
{
    /**
     *
     * @var GemsEscort
     */
    public $escort;

    protected $_subItems = array();

    /**
     * Copy from Zend_Translate_Adapter
     *
     * Translates the given string
     * returns the translation
     *
     * @param  string             $text   Translation string
     * @param  string|Zend_Locale $locale (optional) Locale/Language to use, identical with locale
     *                                    identifier, @see Zend_Locale for more information
     * @return string
     */
    public function _($text, $locale = null)
    {
        return $this->escort->translate->getAdapter()->_($text, $locale);
    }

    public function __construct(GemsEscort $escort)
    {
        $this->escort = $escort;
    }

    /**
     * Adds privileges that are used in this menu item to the array
     *
     * @param array $privileges
     */
    protected function _addUsedPrivileges(array &$privileges, $label)
    {
        if ($this->_subItems) {
            foreach ($this->_subItems as $item) {
                // Skip autofilter action, but include all others
                if ($item->get('action') !== 'autofilter') {
                    $_itemlabel = $label. ($item->get('label') ?: $item->get('privilege'));
                    if ($_privilege = $item->get('privilege')) {

                        if (isset($privileges[$_privilege])) {
                            $privileges[$_privilege] .= "<br/>&nbsp; + " . $_itemlabel;
                        } else {
                            $privileges[$_privilege] = $_itemlabel;
                        }
                    }
                    $item->_addUsedPrivileges($privileges, $_itemlabel . '-&gt;');
                }
            }
        }
    }

    /**
     * Returns a Zend_Navigation creation array for this menu item, with
     * sub menu items in 'pages'
     *
     * @param Gems_Menu_ParameterCollector $source
     * @return array
     */
    protected function _toNavigationArray(Gems_Menu_ParameterCollector $source)
    {
        if ($this->_subItems) {
            $this->sortByOrder();
            $lastParams = null;
            $i = 0;
            $pages = array();
            foreach ($this->_subItems as $item) {
                if (! $item->get('button_only')) {
                    $page = $item->_toNavigationArray($source);

                    if (($this instanceof Gems_Menu_SubMenuItem) &&
                        (! $this->notSet('controller', 'action')) &&
                        isset($page['params'])) {

                        $params = $page['params'];
                        unset($params['reset']); // Ignore this setting

                        if (count($params)) {
                            $class = '';
                        } else {
                            $class = 'noParameters ';
                        }

                        if ((null !== $lastParams) && ($lastParams !== $params)) {
                            $class .= 'breakBefore';
                        } else {
                            $class = trim($class);
                        }

                        if ($class) {
                            if (isset($page['class'])) {
                                $page['class'] .= ' ' . $class;
                            } else {
                                $page['class'] =  $class;
                            }
                        }
                        $lastParams = $params;
                    }
                    $pages[$i] = $page;
                    $i++;
                }
            }

            return $pages;
        }

        return array();
    }

    /**
     * Add a sub item to this item.
     *
     * The argumenets can be any of those used for Zend_Navigation_Page as well as some Gems specials.<ul>
     * <li>'action' The name of the action.</li>
     * <li>'allowed' Is the user allowed to access this menu item. Is checked against ACL using 'privilige'.</li>
     * <li>'button_only' Never in the menu, only shown as a button by the program.</li>
     * <li>'class' Display class for the menu link.</li>
     * <li>'controller' What controller to use.</li>
     * <li>'icon' Icon to display with the label.</li>
     * <li>'label' The label to display for the menu item.</li>
     * <li>'privilege' The privilege needed to choose the item.</li>
     * <li>'target' Optional target attribute for the link.</li>
     * <li>'type' Optional content type for the link</li>
     * <li>'visible' Is the item visible. Is checked against ACL using 'privilige'.</li>
     * </ul>
     *
     * @see Zend_Navigation_Page
     *
     * @param array $args_array MUtil_Ra::args array with defaults 'visible' and 'allowed' true.
     * @return Gems_Menu_SubMenuItem
     */
    protected function add($args_array)
    {
        // Process parameters.
        $args = MUtil_Ra::args(func_get_args(), 0,
            array('visible' => true,    // All menu items are initally visible unless stated otherwise
                'allowed' => true,      // Same as with visible, need this for t_oNavigationArray()
                ));

        if (! isset($args['label'])) {
            $args['visible'] = false;
        }

        if (! isset($args['order'])) {
            $args['order'] = 10 * (count($this->_subItems) + 1);
        }

        $page = new Gems_Menu_SubMenuItem($this->escort, $this, $args);

        $this->_subItems[] = $page;

        return $page;
    }

    /**
     * Add a agenda setup menu tree to the menu
     *
     * @param string $label
     * @param array $other
     * @return Gems_Menu_SubMenuItem
     */
    public function addAgendaSetupMenu($label)
    {
        $setup = $this->addContainer($label);

        $setup->addBrowsePage($this->_('Activities'),      'pr.agenda-activity',  'agenda-activity');
        $setup->addBrowsePage($this->_('Procedures'),      'pr.agenda-procedure', 'agenda-procedure');
        $setup->addBrowsePage($this->_('Locations'),       'pr.locations',        'location');
        $setup->addBrowsePage($this->_('Healtcare staff'), 'pr.agenda-staff',     'agenda-staff');
        $setup->addBrowsePage($this->_('Agenda filters'),  'pr.agenda-filters',   'agenda-filter');

        return $setup;
    }

    /**
     * Add a browse / ceate / edit / show / etc.. menu item
     *
     * @param string $label
     * @param string $privilege
     * @param string $controller
     * @param array $other
     * @return Gems_Menu_SubMenuItem
     */
    public function addBrowsePage($label, $privilege, $controller, array $other = array())
    {
        $page = $this->addPage($label, $privilege, $controller, 'index', $other);
        $page->addAutofilterAction();
        $page->addCreateAction();
        $page->addExcelAction();
        $page->addImportAction();
        $show = $page->addShowAction();
        $show->addEditAction();
        $show->addDeleteAction();

        return $page;
    }

    /**
     * Add a menu item that is never added to the navigation tree and only shows up as a button.
     *
     * @param string $label
     * @param string $privilege
     * @param string $controller
     * @param string $action
     * @param array $other
     * @return Gems_Menu_SubMenuItem
     */
    public function addButtonOnly($label, $privilege, $controller, $action = 'index', array $other = array())
    {
        $other['button_only'] = true;

        return $this->addPage($label, $privilege, $controller, $action, $other);
    }

    /**
     * Add a calendar page to the menu
     *
     * @param string $label
     * @param array $other
     * @return Gems_Menu_SubMenuItem
     */
    public function addCalendarPage($label)
    {
        $page = $this->addPage($label, 'pr.calendar', 'calendar');
        $page->addAutofilterAction();
        $page->addImportAction();

        return $page;
    }

    /**
     * Add a Mail menu tree to the menu
     *
     * @param string $label
     * @param array $other
     * @return Gems_Menu_SubMenuItem
     */
    public function addCommSetupMenu($label)
    {
        $setup = $this->addContainer($label);

        // AUTOMATIC COMMUNICATION CONTROLLER
        $page = $setup->addBrowsePage($this->_('Automatic mail'), 'pr.comm.job', 'comm-job');
        $page->addButtonOnly($this->_('Turn Automatic Mail Jobs OFF'), 'pr.comm.job', 'cron', 'cron-lock');

        $page->addPage($this->_('Run'), 'pr.cron.job', 'cron', 'index');

        $ajaxPage = $this->addPage($this->_('Round Selection'), 'pr.comm.job', 'comm-job', 'roundselect', array('visible' => false));

        // MAIL SERVER CONTROLLER
        $page = $setup->addBrowsePage($this->_('Servers'), 'pr.mail.server', 'mail-server');

        // COMMUNICATION TEMPLATE CONTROLLER
        $setup->addBrowsePage($this->_('Templates'), 'pr.comm.template', 'comm-template');

        // COMMUNICATION ACTIVITY CONTROLLER
        //$setup->addBrowsePage();
        $page = $setup->addPage($this->_('Activity log'), 'pr.mail.log', 'mail-log');
        $page->addAutofilterAction();
        $page->addExcelAction();
        $page->addShowAction();

        return $setup;
    }


    public function addContainer($label, $privilege = null, array $other = array())
    {
        $other['label'] = $label;

        if ($privilege) {
            $other['privilege'] = $privilege;
        }

        // Process parameters.
        $defaults = array(
            'visible' => (boolean) $label,  // All menu containers are initally visible unless stated otherwise or specified without label
            'allowed' => true,              // Same as with visible, need this for t_oNavigationArray()
            'order'   => 10 * (count($this->_subItems) + 1),
            );

        foreach ($defaults as $key => $value) {
            if (! isset($other[$key])) {
                $other[$key] = $value;
            }
        }

        $page = new Gems_Menu_ContainerItem($this->escort, $this, $other);

        $this->_subItems[] = $page;

        return $page;
    }

    /**
     * Shortcut function to create the export container.
     *
     * @param string $label Label for the container
     * @return Gems_Menu_MenuAbstract The new contact page
     */
    public function addExportContainer($label)
    {
        $export = $this->addContainer($label);

        // EXPORT
        $export->addPage($this->_('Survey answers'), 'pr.export', 'export', 'index');
        $export->addButtonOnly('', 'pr.export', 'export', 'handle-export');
        $export->addButtonOnly('', 'pr.export', 'export', 'download');

        // EXPORT TO HTML
        $export->addPage($this->_('Respondent archives'), 'pr.export-html', 'respondent-export', 'index');

        return $export;
    }

    /**
     * Add a file upload/download page to the menu
     *
     * @param string $label         The label to display for the menu item, null for access without display
     * @param string $privilege     The privilege for the item, null is always, 'pr.islogin' must be logged in, 'pr.nologin' only when not logged in.
     * @param string $controller    What controller to use
     * @param string $action        The name of the action
     * @param array  $other         Array of extra options for this item, e.g. 'visible', 'allowed', 'class', 'icon', 'target', 'type', 'button_only'
     * @return Gems_Menu_SubMenuItem
     */
    public function addFilePage($label, $privilege, $controller, array $other = array())
    {
        $page = $this->addPage($label, $privilege, $controller, 'index', $other);
        $page->addAutofilterAction();
        // $page->addCreateAction();
        // $page->addExcelAction();
        $page = $page->addShowAction();
        $page->addEditAction();
        $page->addDeleteAction();
        $page->addButtonOnly($this->_('Download'), $privilege . '.download', $controller, 'download')
                ->setModelParameters(1);

        return $page;
    }

    /**
     * Add a roles browse edit page to the menu,
     *
     * @param string $label
     * @param array $other
     * @return Gems_Menu_SubMenuItem
     */
    public function addGroupsPage($label, array $other = array())
    {
        $page  = $this->addBrowsePage($label, 'pr.group', 'group', $other);
        $user  = $this->escort->loader->getCurrentUser();
        $roles = array();

        if ($user instanceof Gems_User_User) {
            if ($user->hasPrivilege('pr.group')) {
                $roles = $user->getAllowedRoles();
            }
        }
        // MUtil_Echo::track($roles);

        // Now limit changes to allowed roles
        foreach ($page->getChildren() as $showpage) {
            if ($showpage instanceof Gems_Menu_SubMenuItem) {
                if ('show' === $showpage->get('action')) {
                    foreach ($showpage->getChildren() as $subpage) {
                        $subpage->addParameterFilter('ggp_role', $roles);
                    }
                    break;
                }
            }
        }

        return $page;
    }

    /**
     * Shortcut function to create the import container.
     *
     * @param string $label Label for the container
     * @return Gems_Menu_MenuAbstract The new contact page
     */
    public function addImportContainer($label)
    {
        $import = $this->addContainer($label);

        $page = $import->addPage($this->_('Answers'), 'pr.survey-maintenance.answer-import', 'file-import', 'answers-import');
        $page = $import->addFilePage($this->_('Importable'), 'pr.file-import', 'file-import');
        // $page->addButtonOnly($this->_('Auto import'), 'pr.file-import.auto', 'file-import', 'auto');
        $page->addImportAction('pr.file-import.import', array('label' => $this->_('Import file')))
                ->setModelParameters(1);

        $page = $import->addFilePage($this->_('Imported files'), 'pr.file-import', 'imported-files');
        $page = $import->addFilePage($this->_('Imported failures'), 'pr.file-import', 'imported-failures');

        return $import;
    }

    /**
     * Add a Mail menu tree to the menu
     *
     * @param string $label
     * @param array $other
     * @return Gems_Menu_SubMenuItem
     */
    public function addMailSetupMenu($label)
    {
        $setup = $this->addContainer($label);

        // MAIL JOB CONTROLLER
        $page = $setup->addBrowsePage($this->_('Automatic mail'), 'pr.mail.job', 'mail-job');
        $page->addButtonOnly($this->_('Turn Automatic Mail Jobs OFF'), 'pr.mail.job', 'cron', 'cron-lock');
        //$page->addPage($this->_('Run'), null, 'cron', 'index');

        // MAIL CONTROLLER
        $setup->addBrowsePage($this->_('Templates'), 'pr.mail', 'mail-template');

        return $setup;
    }

    /**
     * Add a page to the menu
     *
     * @param string $label         The label to display for the menu item, null for access without display
     * @param string $privilege     The privilege for the item, null is always, 'pr.islogin' must be logged in, 'pr.nologin' only when not logged in.
     * @param string $controller    What controller to use
     * @param string $action        The name of the action
     * @param array  $other         Array of extra options for this item, e.g. 'visible', 'allowed', 'class', 'icon', 'target', 'type', 'button_only'
     * @return Gems_Menu_SubMenuItem
     */
    public function addPage($label, $privilege, $controller, $action = 'index', array $other = array())
    {
        $other['label'] = $label;
        $other['controller'] = $controller;
        $other['action'] = $action;

        if ($privilege) {
            $other['privilege'] = $privilege;
        }

        return $this->add($other);
    }

    public function addPlanPage($label)
    {
        $infoPage = $this->addContainer($label);

        $page = $infoPage->addPage($this->_('Track Summary'), 'pr.plan.summary', 'summary', 'index');
        $page->addAutofilterAction();
        $page->addExcelAction();

        $page = $infoPage->addPage($this->_('Track Compliance'), 'pr.plan.compliance', 'compliance', 'index');
        $page->addAutofilterAction();
        $page->addExcelAction();

        $plans[] = $infoPage->addPage($this->_('By period'), 'pr.plan.overview', 'overview-plan', 'index');
        $plans[] = $infoPage->addPage($this->_('By token'), 'pr.plan.token', 'token-plan', 'index');
        $plans[] = $infoPage->addPage($this->_('By respondent'), 'pr.plan.respondent', 'respondent-plan', 'index');

        foreach ($plans as $plan) {
            $plan->addAutofilterAction();
            $plan->addAction($this->_('Bulk mail'), 'pr.token.bulkmail', 'email', array('routeReset' => false));
            $plan->addExcelAction();
        }

        $page = $infoPage->addPage($this->_('Respondent status'), 'pr.plan.consent', 'consent-plan', 'index');
        $page->addShowAction();
        $page->addExcelAction();

        return $infoPage;
    }

    /**
     * Add pages that show the user technical information about the installation
     * in the project.
     *
     * @param string $label
     * @param array $other
     * @return Gems_Menu_SubMenuItem
     */
    public function addProjectInfoPage($label)
    {
        $page = $this->addPage($label, 'pr.project-information', 'project-information');
        $page->addAction($this->_('Errors'),     null, 'errors');
        $page->addAction($this->_('PHP'),        null, 'php');
        $page->addAction($this->_('PHP Errors'), null, 'php-errors');
        $page->addAction($this->_('Project'),    null, 'project');
        $page->addAction($this->_('Session'),    null, 'session');
        $page->addButtonOnly($this->_('Maintenance mode'), 'pr.maintenance.maintenance-mode', 'project-information', 'maintenance');
        $page->addButtonOnly($this->_('Clean cache'), 'pr.maintenance.clean-cache', 'project-information', 'cacheclean');

        $templates = $page->addPage($this->_('Templates'), 'pr.templates', 'template');
        $templates->addAutofilterAction();
        $templates->addEditAction();

        return $page;
    }

    /**
     * Add pages that show the user an overview of the tracks / surveys used
     * in the project.
     *
     * @param string $label
     * @param array $other
     * @return Gems_Menu_SubMenuItem
     */
    public function addProjectPage($label)
    {
        if ($this->escort instanceof Gems_Project_Tracks_SingleTrackInterface) {
            if ($trackId = $this->escort->getTrackId()) {
                $infoPage = $this->addPage($label, 'pr.project', 'project-tracks', 'show')
                    ->addHiddenParameter(MUtil_Model::REQUEST_ID, $trackId);
                $trackSurveys = $infoPage;
            } else {
                $infoPage = $this->addPage($label, 'pr.project', 'project-tracks');
                $trackSurveys = $infoPage->addShowAction('pr.project');
            }
            $trackSurveys->addAction($this->_('Preview'), 'pr.project.questions', 'questions')
                    ->addNamedParameters(MUtil_Model::REQUEST_ID, 'gro_id_track', Gems_Model::SURVEY_ID, 'gsu_id_survey');

            $infoPage->addAutofilterAction();

            // MUtil_Echo::track($infoPage->_toNavigationArray(array($this->escort->request)));
        } else {
            if ($this->escort instanceof Gems_Project_Tracks_StandAloneSurveysInterface) {
                $infoPage = $this->addContainer($label);
                $tracksPage = $infoPage->addPage($this->_('Tracks'), 'pr.project', 'project-tracks');
                $tracksPage->addAutofilterAction();

                $trackSurveys = $tracksPage->addShowAction('pr.project');
                $trackSurveys->addAction($this->_('Preview'), 'pr.project.questions', 'questions')
                        ->addNamedParameters(MUtil_Model::REQUEST_ID, 'gro_id_track', Gems_Model::SURVEY_ID, 'gsu_id_survey');

                $surveysPage = $infoPage->addPage($this->_('Surveys'), 'pr.project', 'project-surveys');
                $surveysPage->addAutofilterAction();
                $surveysPage->addShowAction('pr.project');
            } else {
                $infoPage = $this->addPage($label, 'pr.project', 'project-tracks');
                $infoPage->addAutofilterAction();
                $trackSurveys = $infoPage->addShowAction('pr.project');
                $trackSurveys->addAction($this->_('Preview'), 'pr.project.questions', 'questions')
                        ->addNamedParameters(MUtil_Model::REQUEST_ID, 'gro_id_track', Gems_Model::SURVEY_ID, 'gsu_id_survey');
            }
        }

        return $infoPage;
    }

    /**
     * Add a staff browse edit page to the menu,
     *
     * @param string $label
     * @param array $other
     * @return Gems_Menu_SubMenuItem
     */
    public function addStaffPage($label, array $other = array())
    {
        $page = $this->addPage($label, 'pr.staff', 'staff', 'index', $other);
        $page->addAutofilterAction();
        $createPage = $page->addCreateAction();
        $showPage = $page->addShowAction();
        $pages[] = $showPage->addEditAction();
        $pages[] = $showPage->addAction($this->_('Reset password'), 'pr.staff.edit', 'reset')->setModelParameters(1);
        $pages[] = $showPage->addAction($this->_('Send Mail'), 'pr.staff.edit', 'mail')->setModelParameters(1);
        $pages[] = $showPage->addDeleteAction();
        $pages[] = $page->addExcelAction();
        $pages[] = $page->addImportAction();
        if (! $this->escort->hasPrivilege('pr.staff.edit.all')) {
            $filter = array_keys($this->escort->loader->getCurrentUser()->getAllowedOrganizations());
            foreach ($pages as $sub_page) {
                $sub_page->setParameterFilter('gsf_id_organization', $filter, 'accessible_role', true);
            }
        }

        return $page;
    }

    /**
     * Add a Trackbuilder menu tree to the menu
     *
     * @param string $label
     * @param array $other
     * @return Gems_Menu_SubMenuItem
     */
    public function addTrackBuilderMenu($label, array $other = array())
    {
        $setup = $this->addContainer($label);

        // SURVEY SOURCES CONTROLLER
        $page = $setup->addPage($this->_('Survey Sources'), 'pr.source', 'source');
        $page->addAutofilterAction();
        $page->addCreateAction();
        $page->addExcelAction();
        $page->addImportAction();
        $show = $page->addShowAction();
        $show->addEditAction();
        $show->addDeleteAction();

        $show->addAction($this->_('Check status'), null, 'ping')
                ->addParameters(MUtil_Model::REQUEST_ID);
        $show->addAction($this->_('Synchronize surveys'), 'pr.source.synchronize', 'synchronize')
                ->addParameters(MUtil_Model::REQUEST_ID);
        $show->addAction($this->_('Check is answered'), 'pr.source.check-answers', 'check')
                ->addParameters(MUtil_Model::REQUEST_ID);
        $show->addAction($this->_('Check attributes'), 'pr.source.check-attributes', 'attributes')
                ->addParameters(MUtil_Model::REQUEST_ID);

        $page->addAction($this->_('Synchronize all surveys'), 'pr.source.synchronize-all', 'synchronize-all');
        $page->addAction($this->_('Check all is answered'), 'pr.source.check-answers-all', 'check-all');
        $page->addAction($this->_('Check all attributes'), 'pr.source.check-attributes-all', 'attributes-all');

        // ADD CHART SETUP CONTROLLER
        $setup->addBrowsePage($this->_('Charts setup'), 'pr.chartsetup', 'chartconfig');

        // SURVEY MAINTENANCE CONTROLLER
        $page = $setup->addPage($this->_('Surveys'), 'pr.survey-maintenance', 'survey-maintenance');
        $page->addAutofilterAction();

        $page->addExcelAction();
        $showPage = $page->addShowAction();
        $showPage->addEditAction();
        $showPage->addAction($this->_('Check is answered'), 'pr.survey-maintenance.check', 'check')
                ->addParameters(MUtil_Model::REQUEST_ID)
                ->setParameterFilter('gsu_active', 1);
        $showPage->addAction($this->_('Import answers'), 'pr.survey-maintenance.answer-import', 'answer-import')
                ->addParameters(MUtil_Model::REQUEST_ID)
                ->setParameterFilter('gsu_active', 1);
        $showPage->addPdfButton($this->_('PDF'), 'pr.survey-maintenance')
                ->addParameters(MUtil_Model::REQUEST_ID)
                ->setParameterFilter('gsu_has_pdf', 1);
        // Multi survey
        $page->addAction($this->_('Check all is answered'), 'pr.survey-maintenance.check-all', 'check-all');
        $page->addAction($this->_('Import answers'), 'pr.survey-maintenance.answer-import', 'answer-imports');

        // TRACK MAINTENANCE CONTROLLER
        $page = $setup->addBrowsePage($this->_('Tracks'), 'pr.track-maintenance', 'track-maintenance');
        $showPage = $this->findItem(array('controller'=>'track-maintenance', 'action'=>'show'));
        $showPage->addButtonOnly($this->_('Copy'),  'pr.track-maintenance.copy', 'track-maintenance', 'copy')
                ->setModelParameters(1);

        // Fields
        $fpage = $showPage->addPage($this->_('Fields'), 'pr.track-maintenance', 'track-fields')
                ->addNamedParameters(MUtil_Model::REQUEST_ID, 'gtf_id_track');
        $fpage->addAutofilterAction();
        $fpage->addCreateAction('pr.track-maintenance.create')
                ->addNamedParameters(MUtil_Model::REQUEST_ID, 'gtf_id_track');
        $fpage = $fpage->addShowAction()
                ->addNamedParameters(MUtil_Model::REQUEST_ID, 'gtf_id_track', 'fid', 'gtf_id_field', 'sub', 'sub');
        $fpage->addEditAction('pr.track-maintenance.edit')
                ->addNamedParameters('fid', 'gtf_id_field', MUtil_Model::REQUEST_ID, 'gtf_id_track', 'sub', 'sub');
        $fpage->addDeleteAction('pr.track-maintenance.delete')
                ->addNamedParameters('fid', 'gtf_id_field', MUtil_Model::REQUEST_ID, 'gtf_id_track', 'sub', 'sub');

        // Standard tracks
        $fpage = $showPage->addPage($this->_('Rounds'), 'pr.track-maintenance', 'track-rounds')
                ->addNamedParameters(MUtil_Model::REQUEST_ID, 'gro_id_track')
                ->setParameterFilter('gtr_track_type', 'T');
        $fpage->addAutofilterAction();
        $fpage->addCreateAction('pr.track-maintenance.create')
                ->addNamedParameters(MUtil_Model::REQUEST_ID, 'gro_id_track');
        $fpage = $fpage->addShowAction()
                ->addNamedParameters(MUtil_Model::REQUEST_ID, 'gro_id_track', Gems_Model::ROUND_ID, 'gro_id_round');
        $fpage->addEditAction('pr.track-maintenance.edit')
                ->addNamedParameters(Gems_Model::ROUND_ID, 'gro_id_round', MUtil_Model::REQUEST_ID, 'gro_id_track');
        $fpage->addDeleteAction('pr.track-maintenance.delete')
                ->addNamedParameters(Gems_Model::ROUND_ID, 'gro_id_round', MUtil_Model::REQUEST_ID, 'gro_id_track')
                ->setParameterFilter('gtr_track_type', 'T');

        // Single survey tracks
        $fpage = $showPage->addPage($this->_('Round'), 'pr.track-maintenance', 'track-round', 'show')
                ->addNamedParameters(MUtil_Model::REQUEST_ID, 'gro_id_track')
                ->setParameterFilter('gtr_track_type', 'S');
        $fpage->addEditAction('pr.track-maintenance.edit')
                ->addNamedParameters(MUtil_Model::REQUEST_ID, 'gro_id_track');

        $fpage->addAction($this->_('Check assignments'), 'pr.track-maintenance.check', 'check-track')
                ->addParameters(MUtil_Model::REQUEST_ID);

        $showPage->addAction($this->_('Check assignments'), 'pr.track-maintenance.check', 'check-track')
                ->addParameters(MUtil_Model::REQUEST_ID);
        $showPage->addAction($this->_('Recalculate fields'), 'pr.track-maintenance.check', 'recalc-fields')
                ->addParameters(MUtil_Model::REQUEST_ID);

        $overviewPage = $page->addPage($this->_('Tracks per org'), 'pr.track-maintenance.trackperorg', 'track-overview', 'index');
        $overviewPage->addExcelAction();
        $overviewPage->addAutofilterAction();

        $page->addAction($this->_('Check all assignments'), 'pr.track-maintenance.check-all', 'check-all');
        $page->addAction($this->_('Recalculate all fields'), 'pr.track-maintenance.check-all', 'recalc-all-fields');

        return $setup;
    }

    /**
     * Set the visibility of the menu item and any sub items in accordance
     * with the specified user role.
     *
     * @param Zend_Acl $acl
     * @param string $userRole
     * @return Gems_Menu_MenuAbstract (continuation pattern)
     */
    protected function applyAcl(Zend_Acl $acl, $userRole)
    {
        if ($this->_subItems) {
            $anyVisible = false;

            foreach ($this->_subItems as $item) {

                $allowed = $item->get('allowed', true);

                if ($allowed && ($privilege = $item->get('privilege'))) {
                    $allowed = $acl->isAllowed($userRole, null, $privilege);
                }

                if ($allowed) {
                    $item->applyAcl($acl, $userRole);
                    $anyVisible = true;
                } else {
                    // As an item can be invisible but allowed,
                    // but not disallowed but visible we need to
                    // set both.
                    $item->set('allowed', false);
                    $item->set('visible', false);
                    $item->setForChildren('allowed', false);
                    $item->setForChildren('visible', false);
                }
            }

            /*/ Do not show a 'container' menu item (that depends for controller
            // on it's children) when no sub item is allowed.
            if ((! $anyVisible) && $this->notSet('controller', 'action')) {
                $this->set('allowed', false);
                $this->set('visible', false);
            } // */
        }

        return $this;
    }

    /**
     *
     * @param <type> $options
     * @param <type> $findDeep
     * @return Gems_Menu_SubMenuItem|null
     */
    protected function findItem($options, $findDeep = true)
    {
        if ($this->_subItems) {
            foreach ($this->_subItems as $item) {
                if ($result = $item->findItem($options, $findDeep)) {
                    return $result;
                }
            }
        }

        return null;
    }

    protected function findItemPath($options)
    {
        if ($this->_subItems) {
            foreach ($this->_subItems as $item) {
                if ($path = $item->findItemPath($options)) {
                    return $path;
                }
            }
        }

        return array();
    }

    protected function findItems($options, array &$results)
    {
        if ($this->_subItems) {
            foreach ($this->_subItems as $item) {
                $item->findItems($options, $results);
            }
        }
    }

    /**
     *
     * @return array of type Gems_Menu_SubMenuItem
     */
    public function getChildren()
    {
        if ($this->_subItems) {
            return $this->_subItems;
        } else {
            return array();
        }
    }

    public function hasChildren()
    {
        return (boolean) $this->_subItems;
    }

    abstract public function isTopLevel();

    abstract public function isVisible();

    /**
     * Copy from Zend_Translate_Adapter
     *
     * Translates the given string using plural notations
     * Returns the translated string
     *
     * @see Zend_Locale
     * @param  string             $singular Singular translation string
     * @param  string             $plural   Plural translation string
     * @param  integer            $number   Number for detecting the correct plural
     * @param  string|Zend_Locale $locale   (Optional) Locale/Language to use, identical with
     *                                      locale identifier, @see Zend_Locale for more information
     * @return string
     */
    public function plural($singular, $plural, $number, $locale = null)
    {
        $args = func_get_args();
        return call_user_func_array(array($this->escort->translate->getAdapter(), 'plural'), $args);
    }

    /**
     * Make sure only the active branch is visible
     *
     * @param array $activeBranch Of Gems_Menu_Menu Abstract items
     * @return Gems_Menu_MenuAbstract (continuation pattern)
     */
    protected function setBranchVisible(array $activeBranch)
    {
        $current = array_pop($activeBranch);

        if ($this->_subItems) {
            foreach ($this->_subItems as $item) {
                if ($item->isVisible()) {
                    if ($item === $current) {
                        $item->set('active', true);
                        $item->setBranchVisible($activeBranch);
                    } else {
                        $item->setForChildren('visible', false);
                    }
                }
            }
        }

        return $this;
    }

    protected function setForChildren($key, $value)
    {
        if ($this->_subItems) {
            foreach ($this->_subItems as $item) {
                $item->set($key, $value);
                if ($item->_subItems) {
                    $item->setForChildren($key, $value);
                }
            }
        }
        return $this;
    }

    /**
     * Sorts the childeren on their order attribute (instead of the order the were added)
     *
     * @return Gems_Menu_MenuAbstract (continuation pattern)
     */
    public function sortByOrder()
    {
        uasort($this->_subItems, array(__CLASS__, 'sortOrder'));

        return $this;
    }

    /**
     * uasort() function for sortByOrder()
     *
     * @see sortByOrder();
     *
     * @param self $aItem
     * @param self $bItem
     * @return int
     */
    public static function sortOrder($aItem, $bItem)
    {
        $a = $aItem->get('order');
        $b = $bItem->get('order');

        if ($a == $b) {
            return 0;
        }

        return $a > $b ? 1 : -1;
    }
 }