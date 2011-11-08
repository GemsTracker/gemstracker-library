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
 * Project Application Core code
 *
 * @package    Gems
 * @subpackage Project
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

// $autoloader->registerNamespace has not yet run!!
include_once('MUtil/Application/Escort.php');

// mb_internal_encoding('UTF-8');

/**
 * Project Application Core code
 *
 * @package    Gems
 * @subpackage Project
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class GemsEscort extends MUtil_Application_Escort
{
    const RECEPTION_OK = 'OK';

    private static $_instanceOfSelf;

    private $_copyDestinations;
    private $_startFirebird;

    /**
     * The menu variable
     *
     * @var Gems_Menu
     */
    public $menu;

    public function _($text, $locale = null)
    {
        if (! isset($this->request)) {
            // Locale is fixed by request.
            $this->setException(new Gems_Exception_Coding('Requested translation before request was made available.'));
        }
        return $this->translate->_($text, $locale);
    }

    /**
     * Constructor
     *
     * @param  Zend_Application|Zend_Application_Bootstrap_Bootstrapper $application
     * @return void
     */
    public function __construct($application)
    {
        parent::__construct($application);

        self::$_instanceOfSelf = $this;

        $firebug = $application->getOption('firebug');
        $this->_startFirebird = $firebug['log'];

        Zend_Session::start();
    }

    /**
     * Function to maintain uniformity of access to variables from the bootstrap object.
     * Copies all variables to the target object.
     *
     * @param Object $object An object who gets all variables from this object.
     * @return void
     */
    protected function _copyVariables($object)
    {
        // Store for _updateVariable
        $this->_copyDestinations[] = $object;

        // Extra object
        $object->escort = $this;

        foreach ($this->getContainer() as $key => $value) {
            // Prevent self referencing
            if ($value !== $object) {
                // MUtil_Echo::r(get_class($value), $key);
                $object->$key = $value;
            }
        }

        // viewRenderer is not in the container so has to be copied separately
        foreach (get_object_vars($this) as $name => $value) {
            if ('_' != $name[0]) {
                // MUtil_Echo::r(get_class($value), $key);
                $object->$name = $value;
            }
        }
    }

    /**
     * Initialize the basepath string holde object.
     *
     * Use $this->basepath to access afterwards
     *
     * @return Gems_Loader
     */
    protected function _initBasepath()
    {
        return $this->createProjectClass('Util_BasePath');
    }

    /**
     * Create a default file cache for the Translate and DB adapters to speed up execution
     *
     * @return Zend_Cache_Core
     */
    protected function _initCache()
    {
        $cache = null;
        $exists = false;

        // Check if APC extension is loaded
        if( extension_loaded('apc') ) {
            $cacheBackend = 'Apc';
            $cacheBackendOptions = array();
            $exists = true;
        } else {
            $cacheBackend = 'File';
            $cacheDir = GEMS_ROOT_DIR . "/var/cache/";
            $cacheBackendOptions = array('cache_dir' => $cacheDir);
            if (!file_exists($cacheDir)) {
                if (@mkdir($cacheDir, 0777, true)) {
                    $exists = true;
                }
            } else {
                $exists = true;
            }
        }

        if ($exists) {
            $cacheFrontendOptions = array('automatic_serialization' => true,
                                          'cache_id_prefix' => GEMS_PROJECT_NAME . '_');

            $cache = Zend_Cache::factory('Core', $cacheBackend, $cacheFrontendOptions, $cacheBackendOptions);
        } else {
            $cache = Zend_Cache::factory('Core', 'Static', array('caching' => false), array('disable_caching' => true));
        }

        Zend_Db_Table_Abstract::setDefaultMetadataCache($cache);
        Zend_Translate::setCache($cache);

        return $cache;
    }

    /**
     * Initialize the logger
     *
     * @return Gems_Log
     */
    protected function _initLogger()
    {
        $logger = Gems_Log::getLogger();

        $log_path = GEMS_ROOT_DIR . '/var/logs';

        try {
            $writer = new Zend_Log_Writer_Stream($log_path . '/errors.log');
        } catch (Exception $exc) {
            $this->bootstrap(array('locale', 'translate'));
            die(sprintf($this->translate->_('Path %s not writable'), $log_path));
        }

        $logger->addWriter($writer);

        // OPTIONAL STARTY OF FIREBUG LOGGING.
        if ($this->_startFirebird) {
            $logger->addWriter(new Zend_Log_Writer_Firebug());
        }

        Zend_Registry::set('logger', $logger);

        return $logger;
    }

    /**
     * Initialize the database.
     *
     * Use $this->db to access afterwards
     *
     * @return Zend_Db
     */
    protected function _initDb()
    {
        // DATABASE CONNECTION
        $resource = $this->getPluginResource('db');
        if (! $resource) {
            // Do not throw error here. Error is throw in frontDispatchLoopStartup()
            // and no, you should not try to access the database in any earlier
            // code anyway.
            //
            // You are free to change this, but then you get an ugly error message on
            // the screen, while this way the ErrorController is used.
            return null;
        }
        $db = $resource->getDbAdapter();

        // Firebug
        if ($this->_startFirebird) {
            $profiler = new Zend_Db_Profiler_Firebug(GEMS_PROJECT_NAME);
            $profiler->setEnabled(true);
            $db->setProfiler($profiler);
        }
        Zend_Db_Table::setDefaultAdapter($db);

        Zend_Registry::set('db', $db);

        return $db;
    }

    /**
     * Initialize the database.
     *
     * Use $this->acl to access afterwards
     *
     * @return MUtil_Acl
     */
    protected function _initAcl()
    {
        $this->bootstrap(array('db', 'loader'));

        $acl = $this->getLoader()->getRoles($this);

        return $acl->getAcl();
    }

    /**
     * Initialize the Project or Gems loader.
     *
     * Use $this->loader to access afterwards
     *
     * @return Gems_Loader
     */
    protected function _initLoader()
    {
        global $GEMS_DIRS;
        return $this->createProjectClass('Loader', $this->getContainer(), $GEMS_DIRS);
    }

    /**
     * Initialize the locale.
     *
     * We use this function instead of the standard application.ini setting to
     * simplify overruling the settings.
     *
     * Also Firefox tends to overrule the locale settings.
     *
     * You can overrule this function to specify your own project translation method / file.
     *
     * Use $this->locale to access afterwards
     *
     * @return Zend_Locale
     */
    protected function _initLocale()
    {
        $this->bootstrap(array('project', 'session'));

        // Get the choosen language
        if (isset($this->session->user_locale)) {
            $localeId = $this->session->user_locale;
            // MUtil_Echo::r('sess: ' . $localeId);

        } else {
            if (isset($this->project->locale, $this->project->locale['default'])) {
                // As set in project
                $localeId = $this->project->locale['default'];
                // MUtil_Echo::r('def: ' . $localeId);

            } elseif (isset($this->project->locales)) {
                // First of the locales array.
                $localeId = reset($this->project->locales);
                // MUtil_Echo::r('locales: ' . $localeId);


            } else {
                // Default.
                $localeId = 'en';
            }

            $this->session->user_locale = $localeId;
        }

        $locale = new Zend_Locale($localeId);

        Zend_Registry::set('Zend_Locale', $locale);

        return $locale;
    }


    /**
     * Initialize the GEMS project component.
     *
     * The project component contains information about this project that are not Zend specific.
     * For example:
     * -- the super administrator,
     * -- the project name, version and description,
     * -- locales used,
     * -- css and image directories used.
     *
     * This is the place for you to store any project specific data that should not be in the code.
     * I.e. if you make a controllor that needs a setting to work, then put the setting in this
     * settings file.
     *
     * Use $this->project to access afterwards
     *
     * @return Gems_Project_ProjectSettings
     */
    protected function _initProject()
    {
        $projectArray = $this->includeFile(APPLICATION_PATH . '/configs/project');

        if ($projectArray instanceof Gems_Project_ProjectSettings) {
            $project = $projectArray;
        } else {
            $project = $this->createProjectClass('Project_ProjectSettings', $projectArray);
        }

        return $project;
    }

    /**
     * Initialize the Gems session.
     *
     * The session contains information on the registered user from @see $this->loadLoginInfo($username)
     * This includes:
     * -- user_id
     * -- user_login
     * -- user_name
     * -- user_role
     * -- user_locale
     * -- user_organization_id
     * -- user_organization_name
     *
     * Use $this->session to access afterwards
     *
     * @return Zend_Session_Namespace
     */
    protected function _initSession()
    {
        $session = new Zend_Session_Namespace('gems.' . GEMS_PROJECT_NAME . '.session');

        if (! isset($session->user_role)) {
            $session->user_role = 'nologin';
        }

        return $session;
    }


    /**
     * Initialize the translate component.
     *
     * Scans the application and project dirs for available translations
     *
     * Use $this->translate to access afterwards
     *
     * @return Zend_Translate
     */
    protected function _initTranslate()
    {
        $this->bootstrap('locale');

        $language = $this->locale->getLanguage();

        /*
         * Scan for files with -<languagecode> and disable notices when the requested
         * language is not found
         */
        $options = array( 'adapter'         => 'gettext',
                          'content'         => GEMS_LIBRARY_DIR . '/languages/',
                          'disableNotices'  => true,
                          'scan'            => Zend_Translate::LOCALE_FILENAME);

        $translate = new Zend_Translate($options);

        // If we don't find the needed language, use a fake translator to disable notices
        if (! $translate->isAvailable($language)) {
            $translate = MUtil_Translate_Adapter_Potemkin::create();
        }

        //Now if we have a project specific language file, add it
        $projectLanguageDir = APPLICATION_PATH . '/languages/';
        if (file_exists($projectLanguageDir)) {
            $options['content'] = $projectLanguageDir;
            $options['disableNotices'] = true;
            $projectTranslations = new Zend_Translate($options);
            //But only when it has the requested language
            if ($projectTranslations->isAvailable($language)) {
                $translate->addTranslation(array('content' => $projectTranslations));
            }
            unset($projectTranslations);  //Save some memory
        }

        $translate->setLocale($language);
        Zend_Registry::set('Zend_Translate', $translate);

        return $translate;
    }


    /**
     * Initialize the util component.
     *
     * You can overrule this function to specify your own project translation method / file.
     *
     * Use $this->util to access afterwards
     *
     * @return Gems_Util
     */
    protected function _initUtil()
    {
        $this->bootstrap(array('basepath', 'loader', 'project'));

        return $this->getLoader()->getUtil();
    }

    /**
     * Initialize the view component and sets some project specific values.
     *
     * Actions taken here can take advantage that the full framework has
     * been activated by now, including session data, etc.
     *
     * Use $this->view to access afterwards
     *
     * @return Zend_View
     */
    protected function _initView()
    {
        // Initialize view
        $view = new Zend_View();
        $view->addHelperPath('MUtil/View/Helper', 'MUtil_View_Helper');
        $view->addHelperPath('Gems/View/Helper', 'Gems_View_Helper');
        $view->doctype('XHTML1_STRICT');
        $view->headTitle($this->project->name);
        $view->setEncoding('UTF-8');
        $view->headMeta('text/html;charset=UTF-8', 'Content-Type', 'http-equiv');

        // Add it to the ViewRenderer
        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');
        $viewRenderer->setView($view);

        // Return it, so that it can be stored by the bootstrap
        return $view;
    }

    protected function _initZFDebug()
    {
        /*
        // if ((APPLICATION_ENV === 'development') &&
        if ((APPLICATION_ENV !== 'production') &&
            (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE 6.') === FALSE)) {

            $autoloader = Zend_Loader_Autoloader::getInstance();
            $autoloader->registerNamespace('ZFDebug');

            $options = array(
                'plugins' => array('Variables',
                    'File' => array('base_path' => '/path/to/project'),
                    'Memory',
                    'Time',
                    'Registry',
                    'Exception')
            );

            # Instantiate the database adapter and setup the plugin.
            # Alternatively just add the plugin like above and rely on the autodiscovery feature.
            $this->bootstrap('db');
            $db = $this->getPluginResource('db');
            $options['plugins']['Database']['adapter'] = $db->getDbAdapter();

            $debug = new ZFDebug_Controller_Plugin_Debug($options);

            $this->bootstrap('frontController');
            $frontController = $this->getResource('frontController');
            $frontController->registerPlugin($debug);
        } // */
    }

    /**
     * Function called if specified in the Project.ini layoutPrepare section before
     * the layout is drawn, but after the rest of the program has run it's course.
     *
     * @return mixed If null nothing is set, otherwise the name of
     * the function is used as Zend_View variable name.
     */
    protected function _layoutContact(array $args = null)
    {
        $menuItem = $this->menu->find(array('controller' => 'contact',  'action' => 'index'));

        if ($menuItem) {
            $contactDiv = MUtil_Html::create()->div(
                $args,
                array('id' => 'contact'));  // tooltip
            $contactDiv->a($menuItem->toHRefAttribute(), $menuItem->get('label'));

            $ul = $menuItem->toUl();
            $ul->class = 'dropdownContent tooltip';
            $contactDiv->append($ul);

            return $contactDiv;
        }
    }

    /**
     * Function called if specified in the Project.ini layoutPrepare section before
     * the layout is drawn, but after the rest of the program has run it's course.
     *
     * @return mixed If null nothing is set, otherwise the name of
     * the function is used as Zend_View variable name.
     */
    protected function _layoutCrumbs(array $args = null)
    {
        // Must be called after _layoutNavigation()

        if ($this->menu && $this->menu->isVisible()) {
            $div = MUtil_Html::create()->div($args + array('id' => 'crumbs'));
            //$div->raw($this->view->navigation()->breadcrumbs());
            $div->raw($this->view->navigation()->breadcrumbs()->setLinkLast(false)->setMinDepth(0)->render());

            return $div;
        }
    }

    /**
     * Function called if specified in the Project.ini layoutPrepare section before
     * the layout is drawn, but after the rest of the program has run it's course.
     *
     * @return mixed If null nothing is set, otherwise the name of
     * the function is used as Zend_View variable name.
     */
    protected function _layoutCss()
    {
        // Set CSS stylescheet(s)
        if (isset($this->project->css)) {
            $projectCss = (array) $this->project->css;
            $projectCss = array_reverse($projectCss);
            foreach ($projectCss as $css) {
                if (is_array($css)) {
                    $media = $css['media'];
                    $url = $css['url'];
                } else {
                    $url = $css;
                    $media = 'screen';
                }
                $this->view->headLink()->prependStylesheet($this->basepath->getBasePath() . '/' . $url, $media);
            }
        }
    }

    /**
     * Function called if specified in the Project.ini layoutPrepare section before
     * the layout is drawn, but after the rest of the program has run it's course.
     *
     * @return mixed If null nothing is set, otherwise the name of
     * the function is used as Zend_View variable name.
     */
    protected function _layoutDojoTheme()
    {
        // DOJO
        if (MUtil_Dojo::usesDojo($this->view)) {
            $dojo = $this->view->dojo();
            if ($dojo->isEnabled()) {
                $dojo->setDjConfigOption('locale', $this->translate->getLocale());
                // $dojo->dojo()->setDjConfigOption('isDebug', true);

                // Include dojo library
                $dojo->setCdnBase(Zend_Dojo::CDN_BASE_GOOGLE);
                // $dojo->setLocalPath($this->basepath->getBasePath() . '/dojo/dojo/dojo.js');

                // Use dojo theme tundra
                $dojoTheme = $this->project->dijit_css_theme ? $this->project->dijit_css_theme : 'tundra';
                $dojo->addStyleSheetModule('dijit.themes.' . $dojoTheme);

                return $dojoTheme;
            }
        }
    }

    /**
     * Function called if specified in the Project.ini layoutPrepare section before
     * the layout is drawn, but after the rest of the program has run it's course.
     *
     * @return mixed If null nothing is set, otherwise the name of
     * the function is used as Zend_View variable name.
     */
    protected function _layoutFavicon()
    {
        // FAVICON
        $icon = isset($this->project->favicon) ? $this->project->favicon : 'favicon.ico';
        if (file_exists(GEMS_WEB_DIR . '/' . $icon)) {
            $this->view->headLink(array(
                'rel' => 'shortcut icon',
                'href' =>  $this->basepath->getBasePath() . '/' . $icon,
                'type' => 'image/x-icon'),
                Zend_View_Helper_Placeholder_Container_Abstract::PREPEND
                );
        }
    }

    /**
     * Function called if specified in the Project.ini layoutPrepare section before
     * the layout is drawn, but after the rest of the program has run it's course.
     *
     * @return mixed If null nothing is set, otherwise the name of
     * the function is used as Zend_View variable name.
     */
    protected function _layoutJQuery()
    {
        // JQUERY
        if (MUtil_JQuery::usesJQuery($this->view)) {
            $jquery = $this->view->jQuery();
            $jquery->uiEnable(); // enable user interface

            if (MUtil_Https::on()) {
                $jquery->setCdnSsl(true);
            }

            // $jquery->setLocalPath('jquery-1.3.2.min.js');

            if (isset($this->project->jquerycss)) {
                foreach ((array) $this->project->jquerycss as $css) {
                    $jquery->addStylesheet($this->basepath->getBasePath() . '/' . $css);
                }
            }

            return true;
        }
    }

    /**
     * Function called if specified in the Project.ini layoutPrepare section before
     * the layout is drawn, but after the rest of the program has run it's course.
     *
     * @return mixed If null nothing is set, otherwise the name of
     * the function is used as Zend_View variable name.
     */
    protected function _layoutLocaleSet(array $args = null)
    {
        // LOCALE
        $currentUri = base64_encode($this->view->url());
        $localeDiv = MUtil_Html::create('span', $args, array('id' => 'languages'));

        // There will always be a localeDiv, but it can be empty
        if (isset($this->project->locales)) {
            foreach ($this->project->locales as $locale) {
                if ($locale == $this->view->locale) {
                    $localeDiv->span(strtoupper($locale));
                } else {
                    $localeDiv->a(array(
                                'controller' => 'language',
                                'action' => 'change-ui',
                                'language' => urlencode($locale),
                                'current_uri' => $currentUri
                            ), strtoupper($locale));
                }
                $localeDiv[] = ' ';
            }
        }
        return $localeDiv;
    }

    /**
     * Function called if specified in the Project.ini layoutPrepare section before
     * the layout is drawn, but after the rest of the program has run it's course.
     *
     * @return mixed If null nothing is set, otherwise the name of
     * the function is used as Zend_View variable name.
     */
    protected function _layoutMessages(array $args = null)
    {
        // Do not trust $messenger being set in the view,
        // after a reroute we have to reinitiate te $messenger.
        $messenger = $this->getMessenger();

        if ($messenger->hasMessages()) {
            $messages = $messenger->getMessages();
        } else {
            $messages = array();
        }

        if ($messenger->hasCurrentMessages()) {
            $messages = array_merge($messages, $messenger->getCurrentMessages());
        }

        if ($messages) {
            foreach ($messages as &$message) {
                // Make sure html is preserved
                if (strlen($message) && ((strpos($message, '<') !== false) || (strpos($message, '&') !== false))) {
                    $message = MUtil_Html::raw($message);
                }
            }

            $ul = MUtil_Html::create()->ul($args + array('class' => 'errors'), $messages);

            $messenger->clearCurrentMessages();

            return $ul;
        }
    }

    /**
     * Function called if specified in the Project.ini layoutPrepare section before
     * the layout is drawn, but after the rest of the program has run it's course.
     *
     * @return mixed If null nothing is set, otherwise the name of
     * the function is used as Zend_View variable name.
     */
    protected function _layoutNavigation()
    {
        // ACL && Menu
        if ($this->menu && $this->menu->isVisible()) {

            // Make sure the actual $request and $controller in use at the end
            // of the dispatchloop is used and make Zend_Navigation object
            $nav = $this->menu->toZendNavigation($this->request, $this->controller);

            // Set the navigation object
            Zend_Registry::set('Zend_Navigation', $nav);

            $zendNav = $this->view->navigation();
            // $zendNav->setAcl($this->acl);  // Not needed with Gems_Menu
            // $zendNav->setRole($this->session->user_role); // is set to nologin when no user
            $zendNav->setUseTranslator(false);

            // Other options
            // $zendNav->breadcrumbs()->setLinkLast(true);
            // $zendNav->breadcrumbs()->setMaxDepth(1);
            // $zendNav->menu()->setOnlyActiveBranch(true);

            return true;
        }

        return false;
    }

    /**
     * Function called if specified in the Project.ini layoutPrepare section before
     * the layout is drawn, but after the rest of the program has run it's course.
     *
     * @return mixed If null nothing is set, otherwise the name of
     * the function is used as Zend_View variable name.
     */
    protected function _layoutOrganizationSwitcher() // Gems_Project_Organization_MultiOrganizationInterface
    {
        if ($this->hasPrivilege('pr.organization-switch')) {
            // Organization switcher
            $orgSwitch = MUtil_Html::create('div', array('id' => 'organizations'));
            $currentUri = base64_encode($this->view->url());


            $url = $this->view->getHelper('url')->url(array(
                        'controller' => 'organization',
                        'action' => 'change-ui'), null, true);
            $orgSwitch->raw('<form method="get" action="' . $url . '"><div><input type="hidden" name="current_uri" value="' . $currentUri . '" /><select name="org" onchange="javascript:this.form.submit();">');
            foreach ($this->getAllowedOrganizations() as $id => $org) {
                $selected = '';
                if ($id == $this->session->user_organization_id) {
                    $selected = ' selected="selected"';

                } else {
                }
                $orgSwitch->raw('<option value="' . urlencode($org) . '"' . $selected . '>' . $org . '</option>');
            }
            $orgSwitch->raw('</select></div></form>');
            return $orgSwitch;
        } else {
            return;
        }
    }

    /**
     * Function called if specified in the Project.ini layoutPrepare section before
     * the layout is drawn, but after the rest of the program has run it's course.
     *
     * @return mixed If null nothing is set, otherwise the name of
     * the function is used as Zend_View variable name.
     */
    protected function _layoutProjectName(array $args = null)
    {
        if (isset($args['tagName'])) {
            $tagName = $args['tagName'];
            unset($args['tagName']);
        } else {
            $tagName = 'h1';
        }
        return MUtil_Html::create($tagName, $this->project->name, $args);
    }

    /**
     * Function called if specified in the Project.ini layoutPrepare section before
     * the layout is drawn, but after the rest of the program has run it's course.
     *
     * @return mixed If null nothing is set, otherwise the name of
     * the function is used as Zend_View variable name.
     */
    protected function _layoutTime(array $args = null)
    {
        return MUtil_Html::create()->div(date('d-m-Y H:i:s'), $args, array('id' => 'time'));
    }

    /**
     * Function called if specified in the Project.ini layoutPrepare section before
     * the layout is drawn, but after the rest of the program has run it's course.
     *
     * @return mixed If null nothing is set, otherwise the name of
     * the function is used as Zend_View variable name.
     */
    protected function _layoutTitle(array $args = null)
    {
        if (is_array($args) && array_key_exists('separator', $args)) {
            $separator = $args['separator'];
        } else {
            $separator = ' - ';
        }

        if ($this->controller instanceof MUtil_Controller_Action) {
            if ($title = $this->controller->getTitle($separator)) {
                $this->view->headTitle($separator . $title);
            }
        }
    }

    /**
     * Function called if specified in the Project.ini layoutPrepare section before
     * the layout is drawn, but after the rest of the program has run it's course.
     *
     * @return mixed If null nothing is set, otherwise the name of
     * the function is used as Zend_View variable name.
     */
    protected function _layoutUser(array $args = null)
    {
        if (isset($this->session->user_name)) {
            return MUtil_Html::create()->div(
                    sprintf($this->_('User: %s'), $this->session->user_name),
                    $args,
                    array('id' => 'username')
                    );
        }
    }

    /**
     * Function called if specified in the Project.ini layoutPrepare section before
     * the layout is drawn, but after the rest of the program has run it's course.
     *
     * @return mixed If null nothing is set, otherwise the name of
     * the function is used as Zend_View variable name.
     */
    protected function _layoutVersion(array $args = null)
    {
        $div = MUtil_Html::create()->div($args, array('id' => 'version'));
        $version = $this->loader->getVersions()->getVersion();
        if ($item = $this->menu->findFirst(array('controller'=>'project-information', 'action'=>'changelog'))->toHRefAttribute()) {
            $link = MUtil_Html::create()->a($version, $item);
        } else {
            $link = $version;
        }

        $div->spaced($this->project->description, $this->translate->_('version'), $link);

        return $div;
    }


    /**
     * Function to maintain uniformity of access to variables from the bootstrap object.
     * Updates selected variable(s) to the objects targeted in _copyVariables.
     *
     * Do this when an object is created or when a non-object variable has changed.
     * You do not need to call this method for changes to objects .
     *
     * @param String|Array $name A property name or array of property names to copy from this
     * object to the previous copy targets.
     * @return void
     */
    protected function _updateVariable($name)
    {
        if ($this->_copyDestinations) {
            $names = (array) $name;

            foreach ($this->_copyDestinations as $object) {
                foreach ($names as $key) {
                    $object->$key = $this->$key;
                }
            }
        }
    }

    public function afterLogin($userName = null)
    {
        if (empty($userName)) {
            $userName = $_POST['userlogin'];
        }
        
        /**
         * Reset number of failed logins
         */
        try {
            $sql = "UPDATE gems__users SET gsu_failed_logins = 0, gsu_last_failed = NULL WHERE gsu_login = ?";
            $this->db->query($sql, array($userName));
        } catch (Exception $e) {
            // swallow exception
        }
    }

    public function afterFailedLogin()
    {
        /**
         * Store the failed login attempt
         */
        try {
            if (isset($_POST['userlogin'])) {
                $sql = "UPDATE gems__users SET gsu_failed_logins = gsu_failed_logins + 1, gsu_last_failed = NOW() WHERE gsu_login = ?";
                $this->db->query($sql, array($_POST['userlogin']));
            }
        } catch (Exception $e) {
            // swallow exception
        }
    }

    public function afterLogout()
    {
        $this->session->unsetAll();
    }

    /**
     * Hook 2: Called in $this->run().
     *
     * This->init() has ran and the constructor has finisched so all _init{name} and application.ini
     * resources have been loaded. The code between the constructor and the call to $this->run() has
     * been executed in $this->run() has hooked $this as both a Zend_Controller_Plugin and a
     * Zend_Controller_Action_Helper.
     *
     * Not initialized are the $request, $response and $controller objects.
     *
     * Previous hook: init()
     * Actions since: $this->_inti{Name}; resources from configuration initialized
     * Actions after: $this->request object created
     * Next hook: requestChanged()
     *
     * @return void
     */
    public function beforeRun()
    {
        //$this->front = $this->getResource('frontController');

        $this->_copyVariables($this->view);
    }


    /**
     * Hook 9: During action controller initialization.
     *
     * This hook is called in the constructor of the controller. Nothing is done and
     * $controller->init has not been called, so this is a good moment to change settings
     * that should influence $controller->init().
     *
     * Previous hook: preDispatch()
     * Actions since: $dispatcher->dispatch(); $controller->__construct()
     * Actions after: $controller->init(); ob_start(); $controller->dispatch()
     * Next hook: controllerBeforeAction()
     *
     * @param Zend_Controller_Action $actionController
     * @return void
     */
    public function controllerInit(Zend_Controller_Action $actionController = null)
    {
        $this->_copyVariables($actionController ? $actionController : $this->controllerAfterAction);

        // Ste the directories where the snippets are/
        if ($actionController instanceof MUtil_Controller_Action) {
            $snippetLoader = $actionController->getSnippetLoader();
            $snippetLoader->addDirectory(GEMS_ROOT_DIR . '/library/Gems/snippets');
            $snippetLoader->addDirectory(APPLICATION_PATH . '/snippets');
            // MUtil_Echo::track($snippetLoader->getDirectories());
        }

        $this->prepareController();

        // Now set some defaults
        $dateFormOptions['dateFormat']   = 'dd-MM-yyyy';
        $dateFormOptions['description']  = 'dd-mm-yyyy';
        $dateFormOptions['size']         =  10;
        $dateFormOptions['jQueryParams'] = array(
            'buttonImage' => $this->getUtil()->getImageUri('datepicker.png'),
            'changeMonth' => true,
            'changeYear' => true,
            'duration' => 'fast',
            'showOn' => 'button',
        );

        Zend_Registry::set('MUtil_Model_FormBridge', array('date' => $dateFormOptions));
    }

    protected function createProjectClass($className, $param1 = null, $param2 = null)
    {
        if (file_exists(APPLICATION_PATH . '/classes/' . GEMS_PROJECT_NAME_UC . '/' . str_replace('_', '/', $className) . '.php')) {
            $className = GEMS_PROJECT_NAME_UC . '_' . $className;
        } else {
            $className = 'Gems_' . $className;
        }

        switch (func_num_args())
        {
            case 1:
                return new $className();

            case 2:
                return new $className($param1);

            case 3:
                return new $className($param1, $param2);

            default:
                throw new Gems_Exception_Coding(__CLASS__ . '->' . __FUNCTION__ . '() called with more parameters than possible.');
        }
    }

    /**
     * Hook 7: Called before Zend_Controller_Front enters its dispatch loop.
     *
     * This events enables you to adjust the request after the routing has been done.
     *
     * This is the final hook before the dispatchLoop starts. All the hooks in the dispatchLoop
     * can be executed more then once.
     *
     * Not yet initialized is the $controller object - as the $controller can change during
     * the dispatchLoop.
     *
     * Previous hook: routeShutdown()
     * Actions since: nothing, but the route consisting of controller, action and module should now be fixed
     * Actions after: dispatch loop started
     * Next hook: preDispatch()
     *
     * @param  Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request)
    {
        // Check the installation
        if (! isset($this->db)) {
            $this->setException(new Gems_Exception_Coding('No database registered in ' . GEMS_PROJECT_NAME . 'Application.ini for key resources.db.'));
        }
    }

    private function findExtension($fullFileName, array $extensions)
    {
        foreach ($extensions as $extension) {
            if (file_exists($fullFileName . '.' . $extension)) {
                return $extension;
            }
        }
    }

    /**
     * Get an array of OrgId => Org Name for all allowed organizations for the current loggedin user
     *
     * @@TODO Make ui to store allowed orgs in staff controller and change function to read these
     *
     * @return array
     */
    public function getAllowedOrganizations($userId = null)
    {
        if (is_null($userId)) $userId = $this->session->user_id;
        if ($userId == $this->session->user_id && isset($this->session->allowedOrgs)) {
            //If user is current user, read from session
            $allowedOrganizations = $this->session->allowedOrgs;
        } else {
            $allowedOrganizations = $this->db->fetchPairs("SELECT gor_id_organization, gor_name FROM gems__organizations WHERE gor_active = 1 ORDER BY gor_name");
        }

        return $allowedOrganizations;
    }

    /**
     *
     * @return int The current active organization id or 0 when not known
     */
    public function getCurrentOrganization()
    {
        /*
        if ($this instanceof Gems_Project_Organization_MultiOrganizationInterface) {
            return $this->getUserOrganization();
        }

        if ($this instanceof Gems_Project_Organization_SingleOrganizationInterface) {
            return $this->getRespondentOrganization();
        }
        */

        if (isset($this->session->user_organization_id)) {
            return $this->session->user_organization_id;
        } else {
            return Gems_Cookies::getOrganization(Zend_Controller_Front::getInstance()->getRequest());
        }
    }

    /**
     *
     * @return int The current user id or 0 when not known.
     */
    public function getCurrentUserId()
    {
        if (isset($this->session->user_id)) {
            return $this->session->user_id;
        } else {
            return 0;
        }
    }

    public function getDatabasePaths()
    {
        $path = APPLICATION_PATH . '/configs/db';
        if (file_exists($path)) {
            $paths[GEMS_PROJECT_NAME] = $path;
        }

        if ($this instanceof Gems_Project_Log_LogRespondentAccessInterface) {
            $paths['gems_log'] = GEMS_LIBRARY_DIR . '/configs/db_log_respondent_access';
        }

        if ($this instanceof Gems_Project_Layout_MultiLayoutInterface) {
            $paths['gems_multi_layout'] = GEMS_LIBRARY_DIR . '/configs/db_multi_layout';
        }

        $paths['gems'] = GEMS_LIBRARY_DIR . '/configs/db';

        return $paths;
    }

    /**
     * Retrieve the GemsEscort object
     *
     * @return GemsEscort
     */
    public static function getInstance()
    {
        return self::$_instanceOfSelf;
    }

    /**
     * Type access to $this->loader
     *
     * @return Gems_Loader Or a subclassed version when specified in the project code
     */
    public function getLoader()
    {
        return $this->loader;
    }

    /**
     * Retrieves / sets the messenger
     *
     * @return Zend_Controller_Action_Helper_FlashMessenger
     */
    public function getMessenger()
    {
        if (! isset($this->view->messenger)) {
            $this->view->messenger = new Zend_Controller_Action_Helper_FlashMessenger();
        }
        return $this->view->messenger;
    }



    /**
     * Type access to $this->util
     *
     * @return Gems_Util Or a subclassed version when specified in the project code
     */
    public function getUtil()
    {
        return $this->util;
    }

    /**
     * Returns true if the given role or role of the current user has the given privilege
     *
     * @param string $privilege
     * @param string $role
     * @return bool
     */
    public function hasPrivilege($privilege, $role = null)
    {
        if (is_null($role)) $role = $this->session->user_role;
        return (! $this->acl) || $this->acl->isAllowed($role, null, $privilege);
    }

    /**
     * Searches and loads ini, xml, php or inc file
     *
     * When no extension is specified the system looks for a file with the right extension,
     * in the order: .ini, .php, .xml, .inc.
     *
     * .php and .inc files run within the context of this object and thus can access all
     * $this-> variables and functions.
     *
     * @param string $fileName A filename in the include path
     * @return mixed false if nothing was returned
     */
    protected function includeFile($fileName)
    {
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);

        if (! $extension) {
            $extension = $this->findExtension($fileName, array('inc', 'ini', 'php', 'xml'));
            $fileName .= '.' . $extension;
        }

        if (file_exists($fileName)) {
            switch ($extension) {
                case 'ini':
                    $config = new Zend_Config_Ini($fileName, APPLICATION_ENV);
                    break;

                case 'xml':
                    $config = new Zend_Config_Xml($fileName, APPLICATION_ENV);
                    break;

                case 'php':
                case 'inc':
                    // Exclude all variables not needed
                    unset($extension);

                    // All variables from this Escort file can be changed in the include file.
                    return include($fileName);
                    break;

                default:
                    throw new Zend_Application_Exception('Invalid configuration file provided; unknown config type ' . $extension);

            }

            return $config->toArray();
        }

        // If the file does not exists it is up to the calling function to do something about it.
        return false;
    }

    public function loadLoginInfo($userName)
    {
        /**
         * Read the needed parameters from the different tables, lots of renames for backward
         * compatibility
         */
        $select = new Zend_Db_Select($this->db);
        $select->from('gems__users', array('user_id' => 'gsu_id_user',
                                          'user_login' => 'gsu_login',
                                          //don't expose the password hash
                                          //'user_password'=>'gsu_password',
                                          ))
                ->join('gems__staff', 'gsu_id_user = gsf_id_user', array(
                                          'user_email'=>'gsf_email',
                                          'user_group'=>'gsf_id_primary_group',
                                          'user_locale'=>'gsf_iso_lang',
                                          'user_logout'=>'gsf_logout_on_survey'))
               ->columns(array('user_name'=>"(concat(coalesce(concat(`gems__staff`.`gsf_first_name`,_utf8' '),_utf8''),coalesce(concat(`gems__staff`.`gsf_surname_prefix`,_utf8' '),_utf8''),coalesce(`gems__staff`.`gsf_last_name`,_utf8'')))"))
               ->join('gems__groups', 'gsf_id_primary_group = ggp_id_group', array('user_role'=>'ggp_role'))
               ->join('gems__organizations', 'gsu_id_organization = gor_id_organization',
                       array('user_organization_id'=>'gor_id_organization', 'user_organization_name'=>'gor_name'))
               ->where('ggp_group_active = ?', 1)
               ->where('gor_active = ?', 1)
               ->where('gsu_active = ?', 1)
               ->where('gsu_login = ?', $userName)
               ->limit(1);

        //For a multi-layout project we need to select the appropriate style too
        if ($this instanceof Gems_Project_Layout_MultiLayoutInterface) {
            $select->columns(array('user_style' => 'gor_style'), 'gems__organizations');
        }


        if ($result = $this->db->fetchRow($select, array(), Zend_Db::FETCH_ASSOC)) {
            // $this->session is a session object so we cannot use $this->session = $result
            foreach ($result as $name => $value) {
                $this->session->$name = $value;
            }

            if ($this instanceof Gems_Project_Organization_MultiOrganizationInterface) {
                //Load the allowed organizations into the session
                $this->session->allowedOrgs = $this->getAllowedOrganizations();
            }
        }
    }

    /**
     * Return a hashed version of the input value.
     *
     * @param string $name Optional name, is here for ModelAbstract setOnSave compatibility
     * @param string $value The value to hash.
     * @param boolean $new Optional is new, is here for ModelAbstract setOnSave compatibility
     * @return string The salted hash as a 32-character hexadecimal number.
     */
    public function passwordHash($name, $value, $new)
    {
        return $this->project->getValueHash($value);
    }
    
    /**
     * Generate random password
     * @return string
     */
    public function getRandomPassword()
    {
        $salt = "abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ0123456789";
        $pass = "";

        srand((double)microtime()*1000000);

        $i = 0;

        while ($i <= 7)
        {
            $num = rand() % strlen($salt);
            $tmp = substr($salt, $num, 1);
            $pass = $pass . $tmp;
            $i++;
        }

        return $pass;
    }
    
    /**
     * Hook 12: Called after an action is dispatched by Zend_Controller_Dispatcher.
     *
     * This callback allows for proxy or filter behavior. By altering the
     * request and resetting its dispatched flag (via {@link
     * Zend_Controller_Request_Abstract::setDispatched() setDispatched(false)}),
     * a new action may be specified for dispatching.
     *
     * Zend_Layout_Controller_Plugin_Layout uses this event to change the output
     * of the $response with the rendering of the layout. As the Layout plugin
     * has a priority of 99, this Escort event will take place before the layout
     * is rendered, unless $this->run() was called with a stackIndex lower than zero.
     *
     * Previous hook: controllerAfterAction()
     * Actions since: ob_get_clean(); $response->appendBody()
     * Actions after: while (! Request->isDispatched()) or back to Hook 8 preDispatch()
     * Next hook: dispatchLoopShutdown()
     *
     * @param  Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function postDispatch(Zend_Controller_Request_Abstract $request)
    {
        if ($request->isDispatched()) {

            // Per project layout preparation
            if (isset($this->project->layoutPrepare)) {
                foreach ($this->project->layoutPrepare as $prepare => $type) {
                    if ($type) {
                        $function = '_layout' . ucfirst($prepare);

                        if (isset($this->project->layoutPrepareArgs, $this->project->layoutPrepareArgs[$prepare])) {
                            $args = $this->project->layoutPrepareArgs[$prepare];
                        } else {
                            $args = array();
                        }

                        $result = $this->$function($args);

                        // When a result is returned, add it to the view,
                        // according to the type method
                        if (null !== $result) {
                            if (is_numeric($type)) {
                                $this->view->$prepare = $result;
                            } else {
                                if (! isset($this->view->$type)) {
                                    $this->view->$type = new MUtil_Html_Sequence();
                                }
                                $sequence = $this->view->$type;
                                $sequence[$prepare] = $result;
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Hook 8: Start of dispatchLoop. Called before an action is dispatched
     * by Zend_Controller_Dispatcher.
     *
     * This callback allows for proxy or filter behavior. By altering the request
     * and resetting its dispatched flag (via {@link Zend_Controller_Request_Abstract::setDispatched()
     * setDispatched(false)}), the current action may be skipped.
     *
     * Not yet initialized is the $controller object - as the $controller can change during
     * the dispatchLoop.
     *
     * Previous hook: dispatchLoopStartup() or new loop
     * Actions since: dispatch loop started
     * Actions after: $dispatcher->dispatch(); $controller->__construct()
     * Next hook: controllerInit()
     *
     * @param  Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        if ($this->session->user_id && $previousRequestParameters = $this->session->previousRequestParameters) {
            unset($previousRequestParameters['save_button']);
            unset($previousRequestParameters['userlogin']);
            unset($previousRequestParameters['password']);

            // fake POST
            if ($this->session->previousRequestMode == 'POST') {
                $this->addMessage($this->_('Take note: your session has expired, your inputs where not saved. Please check the input data and try again'));
                $_POST = $previousRequestParameters;
                $_SERVER['REQUEST_METHOD'] = $this->session->previousRequestMode;
                $this->session->previousRequestMode = null;
            }

            $this->session->previousRequestParameters = null;
        }

        $this->setControllerDirectory($request);
    }

    public function prepareController() {
        if ($this instanceof Gems_Project_Layout_MultiLayoutInterface) {
            $this->layoutSwitch($this->request, $this->session);
        }
    }

    /**
     * Hook 3: Called in $this->setRequest.
     *
     * All resources have been loaded and the $request object is created.
     * Theoretically this event can be triggered multiple times, but this does
     * not happen in a standard Zend application.
     *
     * Not initialized are the $response and $controller objects.
     *
     * Previous hook: beforeRun()
     * Actions since: $this->request object created
     * Actions after: $this->response object created
     * Next hook: responseChanged()
     *
     * @param Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function requestChanged(Zend_Controller_Request_Abstract $request)
    {
        if ($this->project->multiLocale) {
            // Get the choosen language
            $localeId = Gems_Cookies::getLocale($request);

            // Change when $localeId exists and is different from session
            if ($localeId && ($this->locale->getLanguage() !== $localeId)) {
                // MUtil_Echo::r('On cookie ' . $localeId . ' <> ' . $this->locale->getLanguage());

                // Does the locale exist?
                if (isset($this->project->locales[$localeId])) {

                    // Add and implement the choosen locale
                    $this->session->user_locale = $localeId;
                    $this->locale->setLocale($localeId);
                    if (! $this->translate->isAvailable($localeId)) {
                        $languageFilename = APPLICATION_PATH . '/languages/default-' . $localeId . '.mo';
                        if (file_exists($languageFilename)) {
                            $this->translate->addTranslation($languageFilename, $localeId);
                        }
                    }
                    $this->translate->setLocale($localeId);
                }
            }
        }

        // Set the base path, the route is now fixed
        $this->basepath->setBasePath($request->getBasePath());
    }


    /**
     * Hook 4: Called in $this->setResponse.
     *
     * All resources have been loaded and the $request and $response object have been created.
     * Theoretically this event can be triggered multiple times, but this does
     * not happen in a standard Zend application.
     *
     * Not initialized is the $controller object and the routing has not yet been executed.
     *
     * Previous hook: requestChanged()
     * Initialized since: the $this->response object
     * Next hook: routeStartup()
     *
     * @return void
     */
    public function responseChanged(Zend_Controller_Response_Abstract $response)
    {
        $response->setHeader('Expires', '', true);
    }

    /**
     * Hook 6: Called after Zend_Controller_Router has determined the route set by the request.
     *
     * This events enables you to adjust the route after the routing has run it's course.
     *
     * Not initialized is the $controller object.
     *
     * Previous hook: routeStartup()
     * Actions since: $router->route()
     * Actions after: nothing, but the route consisting of controller, action and module should now be fixed
     * Next hook: dispatchLoopStartup()
     *
     * @param  Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function routeShutdown(Zend_Controller_Request_Abstract $request)
    {
        // MUtil_Echo::r($request->getParams(), 'params');
        // MUtil_Echo::r($request->getUserParams(), 'userparams');
        // Load the menu. As building the menu can depend on all resources and the request, we do it here.
        //
        // PS: The REQUEST is needed because otherwise the locale for translate is not certain.
        $this->menu = $this->getLoader()->createMenu($this);
        $this->_updateVariable('menu');

        /**
         * Check if we are in maintenance mode or not. This is triggeren by a file in the var/settings
         * directory with the name lock.txt
         */
        if ($this->getUtil()->getMaintenanceLock()->isLocked()) {
            if ($this->session->user_id && $this->session->user_role !== 'master') {
                //Still allow logoff so we can relogin as master
                if (!('index' == $request->getControllerName() && 'logoff' == $request->getActionName())) {
                    $this->setError(
                        $this->_('Please check back later.'),
                        401,
                        $this->_('System is in maintenance mode'));
                }
            } else {
                $this->addMessage($this->_('System is in maintenance mode'));
                MUtil_Echo::r($this->_('System is in maintenance mode'));
            }
        }

        // Gems does not use index/index
        if (('index' == $request->getControllerName()) && ('index' == $request->getActionName())) {
            // Instead Gems routes to the first available menu item when this is the request target
            if ($menuItem = $this->menu->findFirst(array('allowed' => true, 'visible' => true))) {
                $redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
                $redirector->gotoRoute($menuItem->toRouteUrl($request));
                //$menuItem->applyToRequest($request);
                //$this->setControllerDirectory($request); // Maybe the controller directory to be used changed
            } else {
                $this->setError(
                    $this->_('No access to site.'),
                    401,
                    $this->_('You have no access to this site.'));
                return;
            }

        } else {
            //find first allowed item in the menu
            $menuItem = $this->menu->find(array('allowed'      => true,
                                                'action'       => $request->getActionName(),
                                                'controller'   => $request->getControllerName()));

            // Display error when not having the right priviliges
            if (! ($menuItem && $menuItem->get('allowed'))) {

                // When logged in
                if ($this->session->user_id) {
                    $this->setError(
                        $this->_('No access to page'),
                        403,
                        sprintf($this->_('Access to this page is not allowed for current role: %s.'), $this->session->user_role)
                        );

                } else { // No longer logged in
                    if ($menuItem = $this->menu->findFirst(array('allowed' => true, 'visible' => true))) {
                        $this->addMessage($this->_('You are no longer logged in.'));
                        $this->addMessage($this->_('You must login to access this page.'));

                        // save original request, we will redirect back once the user succesfully logs in
                        $this->session->previousRequestParameters = $request->getParams();
                        $this->session->previousRequestMode = ($request->isPost() ? "POST" : "GET");

                        $redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
                        $redirector->gotoRoute($menuItem->toRouteUrl($request));
                    } else {
                        $this->setError(
                            $this->_('You are no longer logged in.'),
                            401,
                            $this->_('You have no access to this site.'));
                        return;
                    }
                }
            } elseif ($this instanceof Gems_Project_Log_LogRespondentAccessInterface) {
                // If logging is enabled, log this action
                $logAction = $request->getControllerName() . '.' . $request->getActionName();
                Gems_AccessLog::getLog()->log($logAction, $request);
            }
        }

        if (isset($menuItem)) {
            $menuItem->applyHiddenParameters($request, $this->menu->getParameterSource());
            $this->menu->setCurrent($menuItem);
        }
    }

    public function setControllerDirectory(Zend_Controller_Request_Abstract $request)
    {
        // Set Controller directory within dispatch loop to handle forwards and exceptions
        $module              = $request->getModuleName();
        $front               = $this->frontController;
        $controllerFileName  = $front->getDispatcher()->getControllerClass($request) . '.php';

        // MUtil_Echo::r(GEMS_PROJECT_PATH . '/controllers/' . $controllerFileName);

        // Set to project path if that controller exists
        // TODO: Dirs & modules combineren.
        if (file_exists(APPLICATION_PATH . '/controllers/' . $controllerFileName)) {
            $front->setControllerDirectory(APPLICATION_PATH . '/controllers', $module);
        } else {
            $front->setControllerDirectory(GEMS_LIBRARY_DIR . '/controllers', $module);
        }
    }


    public function setError($message, $code = 200, $info = null)
    {
        $this->setException(new Gems_Exception($message, $code, null, $info));
    }

    public function setException(exception $e)
    {
        if (isset($this->response)) {
            $this->response->setException($e);
        } else {
            throw $e;
        }
    }

    /**
     * Adds one or more messages to the session based message store.
     *
     * @param mixed $message_args Can be an array or multiple argemuents. Each sub element is a single message string
     * @return MUtil_Controller_Action
     */
    public function addMessage($message_args)
    {
        $messages  = MUtil_Ra::flatten(func_get_args());
        $messenger = $this->getMessenger();

        foreach ($messages as $message) {
            $messenger->addMessage($message);
        }

        return $this;
    }

    public function tokenMailFields(array $tokenData)
    {
        $locale = isset($tokenData['grs_iso_lang']) ? $tokenData['grs_iso_lang'] : $this->locale;

        $genderHello = $this->getUtil()->getTranslated()->getGenderHello();
        $hello[] = $genderHello[$tokenData['grs_gender']];
        $hello[] = $tokenData['grs_first_name'];
        if ($tokenData['grs_surname_prefix']) {
            $hello[] = $tokenData['grs_surname_prefix'];
        }
        $hello[] = $tokenData['grs_last_name'];

        $genderGreeting = $this->getUtil()->getTranslated()->getGenderGreeting();
        $greeting[] = $genderGreeting[$tokenData['grs_gender']];
        if ($tokenData['grs_surname_prefix']) {
            $greeting[] = $tokenData['grs_surname_prefix'];
        }
        $greeting[] = $tokenData['grs_last_name'];

        $result['{email}']      = $tokenData['grs_email'];
        $result['{first_name}'] = $tokenData['grs_first_name'];
        $result['{full_name}']  = implode(' ', $hello);
        $result['{greeting}']   = implode(' ', $greeting);
        $result['{last_name}']  = ($tokenData['grs_surname_prefix'] ? $tokenData['grs_surname_prefix'] . ' ' : '') . $tokenData['grs_last_name'];
        array_shift($hello);
        $result['{name}']       = implode(' ', $hello);

        $result['{organization}']            = $tokenData['gor_name'];
        $result['{organization_location}']   = $tokenData['gor_location'];
        $result['{organization_reply_name}'] = $tokenData['gor_contact_name'];
        $result['{organization_reply_to}']   = $tokenData['gor_contact_email'];
        $result['{organization_signature}']  = $tokenData['gor_signature'];
        $result['{organization_url}']        = $tokenData['gor_url'];
        $result['{organization_welcome}']    = $tokenData['gor_welcome'];

        $result['{physician}'] = ($tokenData['gsf_surname_prefix'] ? $tokenData['grs_surname_prefix'] . ' ' : '') . $tokenData['gsf_last_name'];

        $result['{round}']     = $tokenData['gto_round_description'];

        $result['{site_ask_url}'] = $this->util->getCurrentURI('ask/');

        $result['{survey}'] = $tokenData['gsu_survey_name'];

        $url       = $this->util->getCurrentURI('ask/forward/' . MUtil_Model::REQUEST_ID . '/' . $tokenData['gto_id_token']);
        $url_input = $result['{site_ask_url}'] . 'index/' . MUtil_Model::REQUEST_ID . '/' . $tokenData['gto_id_token'];

        $result['{token}']           = strtoupper($tokenData['gto_id_token']);
        $result['{token_from}']      = MUtil_Date::format($tokenData['gto_valid_from'],  Zend_Date::DATE_LONG, 'yyyy-MM-dd', $locale);
        // $result['{token_input}']     = MUtil_Html::create()->a($url_input, $tokenData['gsu_survey_name']);
        // $result['{token_link}']      = MUtil_Html::create()->a($url, $tokenData['gsu_survey_name']);
        // $result['{token_link}']      = '<a href="' . $url . '">' . $tokenData['gsu_survey_name'] . '</a>';
        $result['{token_link}']      = '[url=' . $url . ']' . $tokenData['gsu_survey_name'] . '[/url]';

        $result['{token_until}']     = MUtil_Date::format($tokenData['gto_valid_until'], Zend_Date::DATE_LONG, 'yyyy-MM-dd', $locale);
        $result['{token_url}']       = $url;
        $result['{token_url_input}'] = $url_input;

        $result['{track}']           = $tokenData['gtr_track_name'];

        return $result;
    }
}

