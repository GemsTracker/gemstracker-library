<?php

/**
 * Project Application Core code
 *
 * @package    Gems
 * @subpackage Project
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

use \MUtil\Controller\Router\Rewrite;

/**
 * Project Application Core code
 *
 * @package    Gems
 * @subpackage Project
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class GemsEscort extends \MUtil_Application_Escort
{
    /**
     * Default reception code value
     */
    const RECEPTION_OK = 'OK';

    /**
     * Static instance
     *
     * @var self
     */
    private static $_instanceOfSelf;

    /**
     * Targets for _updateVariable
     *
     * @var array
     */
    private $_copyDestinations;

    /**
     * The prefix / directory paths where the Gems Loaders should look
     *
     * @var array prefix => path
     */
    private $_loaderDirs;

    /**
     * The project loader
     *
     * @var \MUtil_Loader_PluginLoader
     */
    private $_projectLoader;

    /**
     * Is firebird logging on (set by constructor from application.ini)
     *
     * @var boolean
     */
    private $_startFirebird;

    /**
     * A nested array containing the pages accessible to everyone in maintenance mode
     *
     * @var array Nested controllername => [actions]
     */
    protected $maintenanceAccessiblePages = [
        'index' => ['index', 'login', 'logoff', 'resetpassword'],
        'ask' => ['index', 'forward', 'return', 'token'],
        'contact'=> ['index', 'about', 'gems', 'bugs', 'support'],
    ];

    /**
     * Set to true for bootstrap projects. Needs html5 set to true as well
     * @var boolean
     */
    public $useBootstrap = false;

    /**
     * Set to true for html 5 projects
     *
     * @var boolean
     */
    public $useHtml5 = false;

    /**
     * Constructor
     *
     * @param  \Zend_Application|\Zend_Application_Bootstrap_Bootstrapper $application
     * @return void
     */
    public function __construct($application)
    {
        parent::__construct($application);

        self::$_instanceOfSelf = $this;

        // DIRECTORIES USED BY LOADER
        $dirs = $this->getOption('loaderDirs');
        if ($dirs) {
            $newDirs = array();
            foreach ($dirs as $key => $path) {
                if (defined($key)) {
                    $newDirs[constant($key)] = $path;
                } else {
                    $newDirs[$key] = $path;
                }
            }
            $dirs = $newDirs;
        } else {
            global $GEMS_DIRS;

            // Use $GEMS_DIRS if defined
            if (isset($GEMS_DIRS)) {
                $dirs = array();

                foreach ($GEMS_DIRS as $prefix => $dir) {
                    $dirs[$prefix] = $dir . '/' . strtr($prefix, '_', '/');
                }
            } else {
                // Default setting
                $dirs = array(
                        GEMS_PROJECT_NAME_UC => APPLICATION_PATH . '/classes/' .GEMS_PROJECT_NAME_UC,
                        'Gems' =>               GEMS_LIBRARY_DIR . '/classes/Gems'
                );
            }
        }
        // \MUtil_Echo::track($dirs);
        $this->_loaderDirs = array_reverse($dirs);

        foreach ($this->_loaderDirs as $prefix => $path) {
            if ($prefix) {
                \MUtil_Model::addNameSpace($prefix);
            }
        }

        // PROJECT LOADER
        $this->_projectLoader = new \MUtil_Loader_PluginLoader($this->_loaderDirs);

        // FIRE BUG
        $firebug = $application->getOption('firebug');
        $this->_startFirebird = $firebug['log'];

        // START SESSIE
        $sessionOptions['name']            = GEMS_PROJECT_NAME_UC . '_' . md5(APPLICATION_PATH) . '_SESSID';
        $sessionOptions['cookie_path']     = strtr(dirname($_SERVER['SCRIPT_NAME']), '\\', '/');
        $sessionOptions['cookie_httponly'] = true;
        $sessionOptions['cookie_secure']   = (APPLICATION_ENV == 'production') || (APPLICATION_ENV === 'acceptance');
        \Zend_Session::start($sessionOptions);
    }

    /**
     * Copy from \Zend_Translate_Adapter
     *
     * Translates the given string
     * returns the translation
     *
     * @param  string             $text   Translation string
     * @param  string|\Zend_Locale $locale (optional) Locale/Language to use, identical with locale
     *                                    identifier, @see \Zend_Locale for more information
     * @return string
     */
    public function _($text, $locale = null)
    {
        if (! isset($this->request)) {
            // Locale is fixed by request.
            $this->setException(new \Gems_Exception_Coding('Requested translation before request was made available.'));
        }
        return $this->translateAdapter->_($text, $locale);
    }

    /**
     * Check all parameters for security violations
     *
     * All variables are checked for name contents: the <>=%&"' characters should not occur in the name.
     *
     * The non-post variables are checked for value contents as well: after a <>&% there should not occur
     * a frame, script or img stext.
     *
     * @param array $params
     * @param array $posts
     */
    protected function _checkParameters(array $params, array $posts)
    {
        foreach ($params as $key => $value) {
            $rest = strpbrk($key, '<>=%&"\'');
            if (false !== $rest) {
                $this->setError(
                        $this->_('Illegal request parameter'),
                        422,
                        sprintf($this->_('Illegal character %s in parameter name.'), $rest[0]),
                        true
                );
            }
            if ($value && (!is_object($value)) && (!array_key_exists($key, $posts))) {
                foreach ((array) $value as $val) {
                    // Quickfix
                    // If $val is an array preg_match fails. This is true for export options
                    // If the elements of the array should be checked too, feel free to do so
                    if (is_array($val)) {
                        continue;
                    }
                    // Find not allowed words after <>%&
                    $checks  = ['iframe', 'img', 'script'];
                    $pattern = '/([<>%&])(' . join('|', $checks) . ')/i';
                    if (preg_match($pattern, $val, $matches)) {
                        $this->setError(
                                $this->_('Illegal request parameter'),
                                422,
                                sprintf($this->_('Illegal parameter value containing the text "%s" after a %s character.'), $matches[2], $matches[1]),
                                true
                        );
                    }
                }
            }
        }
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
                // \MUtil_Echo::r(get_class($value), $key);
                // echo get_class($value) . ' => ' . $key . '<br/>';
                $object->$key = $value;
            }
        }

        // viewRenderer is not in the container so has to be copied separately
        foreach (get_object_vars($this) as $name => $value) {
            if ('_' != $name[0]) {
                // \MUtil_Echo::r(get_class($value), $key);
                $object->$name = $value;
            }
        }
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
     * @return \Gems_Project_ProjectSettings
     */
    protected function _initProject()
    {
        $projectArray = $this->includeFile(APPLICATION_PATH . '/configs/project');

        if ($projectArray instanceof \Gems_Project_ProjectSettings) {
            $project = $projectArray;
        } else {
            $project = $this->createProjectClass('Project_ProjectSettings', $projectArray);
        }

        return $project;
    }

    /**
     * Initialize the logger
     *
     * @return \Gems_Log
     */
    protected function _initLogger()
    {
        $this->bootstrap('project');    // Make sure the project object is available
        $logger = \Gems_Log::getLogger();

        $logPath = GEMS_ROOT_DIR . '/var/logs';

        try {
            $writer = new \Zend_Log_Writer_Stream($logPath . '/errors.log');
        } catch (Exception $exc) {
            try {
                // Try to solve the problem, otherwise fail heroically
                \MUtil_File::ensureDir($logPath);
                $writer = new \Zend_Log_Writer_Stream($logPath . '/errors.log');
            } catch (Exception $exc) {
                $this->bootstrap(array('locale', 'translate'));
                die(str_replace(GEMS_ROOT_DIR . '/', '', sprintf(
                        $this->translateAdapter->_('Path %s not writable') . "\n%s\n",
                        $logPath,
                        $exc->getMessage()
                        )));
            }
        }

        $filter = new \Zend_Log_Filter_Priority($this->project->getLogLevel());
        $writer->addFilter($filter);
        $logger->addWriter($writer);

        // OPTIONAL STARTY OF FIREBUG LOGGING.
        if ($this->_startFirebird) {
            $logger->addWriter(new \Zend_Log_Writer_Firebug());
            //We do not add the logLevel here, as the firebug window is intended for use by
            //developers only and it is only written to the active users' own screen.
        }

        \Zend_Registry::set('logger', $logger);

        return $logger;
    }

    /**
     * Create a default file cache for the Translate and DB adapters to speed up execution
     *
     * @return \Zend_Cache_Core
     */
    protected function _initCache()
    {
        $this->bootstrap('project');

        $useCache = $this->getResource('project')->getCache();

        $cache       = null;
        $exists      = false;
        $cachePrefix = GEMS_PROJECT_NAME . '_';

        $defaultLifetime = null;
        // Check if APC extension is loaded and enabled
        if (\MUtil_Console::isConsole() && !ini_get('apc.enable_cli') && $useCache === 'apc') {
            // To keep the rest readable, we just fall back to File when apc is disabled on cli
            $useCache = "file";
        }


        switch ($useCache) {
            case 'newFile':
                if (!class_exists('\Symfony\Component\Cache\Adapter\FilesystemAdapter')) {
                    error_log("Symfony filesystem cache not available!");
                    break;
                }
                $namespace = '';
                $cacheDir = GEMS_ROOT_DIR . '/var/cache';
                //$cache = new Symfony\Component\Cache\Simple\FilesystemCache($namespace, $defaultLifetime, $directory);
                $cache = new \Symfony\Component\Cache\Adapter\TagAwareAdapter(
                    new \Symfony\Component\Cache\Adapter\FilesystemAdapter($namespace, $defaultLifetime, $cacheDir)
                );
                $cacheBackend = new \Gems\Cache\Backend\Psr6Cache($cache);
                $cacheBackendOptions = [];
                if (!file_exists($cacheDir)) {
                    if (@mkdir($cacheDir, 0777, true)) {
                        $exists = true;
                    }
                } else {
                    $exists = true;
                }
                break;
            case 'newZendFile':
                if (!class_exists('\Zend\Cache\StorageFactory')) {
                    error_log("Zend\Cache Filesystem cache not available!");
                    break;
                }
                $cacheDir = GEMS_ROOT_DIR . "/var/cache/";
                $cacheBackendOptions = array('cache_dir' => $cacheDir, 'cache_file_perm' => 0660);

                $storage = \Zend\Cache\StorageFactory::factory([
                    'adapter' => [
                        'name' => 'filesystem',
                        'options' => [
                            'cache_dir' => $cacheDir,
                            'file_permission' => 0660,
                        ],
                    ],
                    'plugins' => array(
                        // Don't throw exceptions on cache errors
                        /*'exception_handler' => array(
                            'throw_exceptions' => false
                        ),*/
                        // We store database rows on filesystem so we need to serialize them
                        'Serializer'
                    )
                ]);

                $cacheBackend = new \Gems\Cache\Backend\ZendCache($storage);

                if (!file_exists($cacheDir)) {
                    if (@mkdir($cacheDir, 0777, true)) {
                        $exists = true;
                    }
                } else {
                    $exists = true;
                }
                break;
            case 'newApc':

                if (!class_exists('\Symfony\Component\Cache\Adapter\ApcuAdapter')) {
                    error_log("Symfony APCU cache not available!");
                    break;
                }
                    if (extension_loaded('apc') && ini_get('apc.enabled')) {
                    //Add path to the prefix as APC is a SHARED cache
                    $cachePrefix .= md5(APPLICATION_PATH);
                    $cacheBackendOptions = array('cache_id_prefix' => $cachePrefix);

                    $cache = new \Symfony\Component\Cache\Adapter\TagAwareAdapter(
                        new \Symfony\Component\Cache\Adapter\ApcuAdapter($cachePrefix, $defaultLifetime)
                    );
                    $cacheBackend = new \Gems\Cache\Backend\Psr6Cache($cache);
                    $exists = true;
                    break;
                }
                // Intentional fall through;

            case 'apc':
            case 'oldApc':
                if (extension_loaded('apc') && ini_get('apc.enabled')) {
                    //Add path to the prefix as APC is a SHARED cache
                    $cachePrefix .= md5(APPLICATION_PATH);
                    $cacheBackendOptions = array('cache_id_prefix' => $cachePrefix);
                    $cacheBackend = new \Gems\Cache\Backend\Apc($cacheBackendOptions);
                    $exists = true;
                    break;
                } else {
                    error_log("APC cache extension not available! defaulting to file.");
                }
            // Intentional fall through;
            case 'file':
            case 'oldFile':
            default:
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

        if ($exists && $useCache <> 'none') {
            /**
             * automatic_cleaning_factor disables automatic cleaning of the cache and should get rid of
             *                           random delays on heavy traffic sites with File cache. Apc does
             *                           not support automatic cleaning.
             */
            $cacheFrontendOptions = array('automatic_serialization' => true,
                'cache_id_prefix' => $cachePrefix,
                'automatic_cleaning_factor' => 0);

            $cache = \Zend_Cache::factory('Core', $cacheBackend, $cacheFrontendOptions, $cacheBackendOptions);
        } else {
            $cache = \Zend_Cache::factory('Core', 'Static', array('caching' => false), array('disable_caching' => true));
        }

        \Zend_Db_Table_Abstract::setDefaultMetadataCache($cache);
        \Zend_Translate::setCache($cache);
        \Zend_Locale::setCache($cache);

        return $cache;
    }

    /**
     * Initialize the database.
     *
     * Use $this->db to access afterwards
     *
     * @return \Zend_Db
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
            $profiler = new \Zend_Db_Profiler_Firebug(GEMS_PROJECT_NAME);
            $profiler->setEnabled(true);
            $db->setProfiler($profiler);
        }
        \Zend_Db_Table::setDefaultAdapter($db);

        \Zend_Registry::set('db', $db);

        return $db;
    }

    /**
     * Initialize the Project or Gems loader.
     *
     * Use $this->loader to access afterwards
     *
     * @return \Gems_Loader
     */
    protected function _initLoader()
    {
        $loader = $this->createProjectClass('Loader', $this->getContainer(), $this->_loaderDirs);

        \MUtil_Model::setSource($loader, true);

        return $loader;
    }

    /**
     * Initialize the access log.
     *
     * Use $this->accesslog to access afterwards
     *
     * @return \Gems_AccessLog
     */
    protected function _initAccesslog()
    {
        $this->bootstrap(array('cache', 'db', 'loader'));

        $accesslog = $this->createProjectClass('AccessLog', $this->cache, $this->db, $this->loader);

        return $accesslog;
    }

    /**
     * Initialize the database.
     *
     * Use $this->acl to access afterwards
     *
     * @return \MUtil_Acl
     */
    protected function _initAcl()
    {
        $this->bootstrap(array('db', 'loader', 'logger'));

        $acl = $this->getLoader()->getRoles($this);

        return $acl->getAcl();
    }

    /**
     * Does nothing but add's the Gems Actionhelper path
     */
    protected function _initActionHelpers()
    {
        \Zend_Controller_Action_HelperBroker::addPrefix('Gems_Controller_Action_Helper');
    }

    /**
     * Initialize the basepath string holde object.
     *
     * Use $this->basepath to access afterwards
     *
     * @return \Gems_Util_BasePath
     */
    protected function _initBasepath()
    {
        return $this->createProjectClass('Util_BasePath');
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
     *
     * Use $this->session to access afterwards
     *
     * @deprecated since 1.5
     * @return \Zend_Session_Namespace
     */
    protected function _initSession()
    {
        $this->bootstrap('project'); // Make sure the project is available
        $session = new \Zend_Session_Namespace('gems.' . GEMS_PROJECT_NAME . '.session');

        $idleTimeout = $this->project->getSessionTimeOut();

        $session->setExpirationSeconds($idleTimeout);

        if (! isset($session->user_role)) {
            $session->user_role = 'nologin';
        }

        // Since userloading can clear the session, we put stuff that should remain (like redirect info)
        // in a different namespace that we call a 'static session', use getStaticSession to access.
        $this->staticSession = new \Zend_Session_Namespace('gems.' . GEMS_PROJECT_NAME . '.sessionStatic');

        return $session;
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
     * @return \Zend_Locale
     */
    protected function _initLocale()
    {
        $this->bootstrap(array('project', 'session'));

        // Get the choosen language
        if (isset($this->session->user_locale)) {
            $localeId = $this->session->user_locale;
            // \MUtil_Echo::r('sess: ' . $localeId);

        } else {
            if (isset($this->project->locale, $this->project->locale['default'])) {
                // As set in project
                $localeId = $this->project->locale['default'];
                // \MUtil_Echo::r('def: ' . $localeId);

            } elseif (isset($this->project->locales)) {
                // First of the locales array.
                $localeId = reset($this->project->locales);
                // \MUtil_Echo::r('locales: ' . $localeId);


            } else {
                // Default.
                $localeId = 'en';
            }

            $this->session->user_locale = $localeId;
        }

        $locale = new \Zend_Locale($localeId);

        \Zend_Registry::set('Zend_Locale', $locale);

        return $locale;
    }

    /**
     * Set the default mailtransport to our own sendmail version
     *
     * This is needed to make sure a correct sender is set when using sendmail.
     * Feel free to set a different default transport method in your project
     * when needed.
     */
    public function _initMailTransport()
    {
        $transport = new \Gems_Mail_Transport_SendMail();
        \Zend_Mail::setDefaultTransport($transport);
    }

    /**
     * Initialize the OpenRosa survey source
     */
    protected function _initOpenRosa()
    {
        $this->bootstrap(array('loader', 'translate'));

        if ($this->getOption('useOpenRosa')) {
            // First handle dependencies
            $this->bootstrap(array('db', 'loader', 'util'));

            $this->getLoader()->addPrefixPath('OpenRosa', GEMS_LIBRARY_DIR . '/classes/OpenRosa', true);

            /**
             * Add Source for OpenRosa
             */
            $tracker = $this->loader->getTracker();
            $tracker->addSourceClasses(array('OpenRosa'=>'OpenRosa form'));
        }
    }

    /**
     * Initialize the translate component.
     *
     * Scans the application and project dirs for available translations
     *
     * Use $this->translate to access afterwards
     * Also sets $this->translateAdapter to access afterwards
     *
     * @return \Zend_Translate
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
                          'scan'            => \Zend_Translate::LOCALE_FILENAME);

        $translate = new \Zend_Translate($options);

        // If we don't find the needed language, use a fake translator to disable notices
        if (! $translate->isAvailable($language)) {
            $translate = \MUtil_Translate_Adapter_Potemkin::create();
        }

        //Now if we have a project specific language file, add it
        $projectLanguageDir = APPLICATION_PATH . '/languages/';
        if (file_exists($projectLanguageDir)) {
            $options['content']        = $projectLanguageDir;
            $options['disableNotices'] = true;
            $projectTranslations       = new \Zend_Translate($options);
            //But only when it has the requested language
            if ($projectTranslations->isAvailable($language)) {
                $translate->addTranslation(array('content' => $projectTranslations));
            }
            unset($projectTranslations);  //Save some memory
        }

        $translate->setLocale($language);
        \Zend_Registry::set('Zend_Translate', $translate);

        // Fix for _init resource being case insensitive
        $container = $this->getContainer();
        $adapter   = $translate->getAdapter();
        $container->translateAdapter = $adapter;
        $this->translateAdapter      = $adapter;

        return $translate;
    }

    /**
     * Initialize the util component.
     *
     * You can overrule this function to specify your own project translation method / file.
     *
     * Use $this->util to access afterwards
     *
     * @return \Gems_Util
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
     * @return \Zend_View
     */
    protected function _initView()
    {
        $this->bootstrap('project');

        // Initialize view
        $view = new \Zend_View();
        $view->addHelperPath('MUtil/View/Helper', 'MUtil_View_Helper');
        $view->addHelperPath('MUtil/Less/View/Helper', 'MUtil_Less_View_Helper');
        $view->addHelperPath('Gems/View/Helper', 'Gems_View_Helper');
        $view->addScriptPath(GEMS_LIBRARY_DIR . '/views/scripts');
        $view->headTitle($this->project->getName());
        $view->setEncoding('UTF-8');

        $metas    = $this->project->getMetaHeaders();
        $headMeta = $view->headMeta();
        foreach ($metas as $httpEquiv => $content) {
            $headMeta->appendHttpEquiv($httpEquiv, $content);
        }

        if ($this->useHtml5) {
            $view->doctype(\Zend_View_Helper_Doctype::HTML5);
        } else {
            $view->doctype(\Zend_View_Helper_Doctype::XHTML1_STRICT);
        }

        // Add it to the ViewRenderer
        $viewRenderer = \Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');
        $viewRenderer->setView($view);

        // Return it, so that it can be stored by the bootstrap
        return $view;
    }

    /**
     * Initialize the currentUser component.
     *
     * You can overrule this function to specify your own project translation method / file.
     *
     * Use $this->currentUser to access afterwards
     *
     * @return \Gems_User_User
     */
    protected function _initCurrentUser()
    {
        $this->bootstrap(array('acl', 'basepath', 'cache', 'db', 'loader', 'project', 'session', 'translate', 'util'));

        // Fix for _init resourcea being case insensitive
        $container = $this->getContainer();
        $user      = $this->loader->getCurrentUser();
        $container->currentUser = $user;

        return $user;
    }

    /**
     * Initialize the loader as source component.
     *
     * You can overrule this function to specify your own project translation method / file.
     *
     * Use $this->source to access afterwards
     *
     * @return \Gems_Loader
     */
    protected function _initSource()
    {
        $this->bootstrap(array('loader'));

        return $this->getLoader();
    }

    /**
     * Add ZFDebug info to the page output.
     *
     * @return void
     **/
    protected function _initZFDebug()
    {
        if ((APPLICATION_ENV === 'production') || (APPLICATION_ENV === 'acceptance') || Zend_Session::$_unitTestEnabled ) {
            // Never on on production systems
            return;
        }

        $debug = $this->getOption('zfdebug');
        if (! isset($debug['activate']) || ('1' !== $debug['activate'])) {
            // Only turn on when activated
            return;
        }

        # Instantiate the database adapter and cache
        $this->bootstrap('db');
        $db = $this->getPluginResource('db');

        $this->bootstrap('cache');
        $cache = $this->cache;

        $options = array(
            'plugins' => array('Variables',
                'Database' => array('adapter' => $db->getDbAdapter()),
                'File'     => array('basePath' => GEMS_ROOT_DIR),
                'Cache'    => array('backend' => $cache->getBackend()),
                'Exception')
        );

        $debugPlugin = new \ZFDebug_Controller_Plugin_Debug($options);

        $this->bootstrap('frontController');
        $frontController = $this->getResource('frontController');
        $frontController->registerPlugin($debugPlugin);
    }

    /**
     * Function called if specified in the Project.ini layoutPrepare section before
     * the layout is drawn, but after the rest of the program has run it's course.
     *
     * @return mixed If null nothing is set, otherwise the name of
     * the function is used as \Zend_View variable name.
     */
    protected function _layoutContact(array $args = null)
    {
        if ($this->menu instanceof \Gems_Menu) {
            $menuItem = $this->menu->find(array('controller' => 'contact', 'action' => 'index'));

            if ($menuItem) {
                $contactDiv = \MUtil_Html::create()->div(
                    $args,
                    array('id' => 'contact')
                );  // tooltip
                $contactDiv->a($menuItem->toHRefAttribute(), $menuItem->get('label'));

                // List may be empty
                if ($ul = $menuItem->toUl()) {
                    $ul->class = 'dropdownContent tooltip';
                    $contactDiv->append($ul);
                }

                return $contactDiv;
            }
        }
    }

    /**
     * Function called if specified in the Project.ini layoutPrepare section before
     * the layout is drawn, but after the rest of the program has run it's course.
     *
     * @return mixed If null nothing is set, otherwise the name of
     * the function is used as \Zend_View variable name.
     */
    protected function _layoutCrumbs(array $args = null)
    {
        // Must be called after _layoutNavigation()

        if ($this->menu && $this->menu->isVisible()) {
            $path = $this->menu->getActivePath($this->request);
            $last = array_pop($path);

            // Only display when there is a path of more than one step or always is on
            if ($path || (isset($args['always']) && $args['always'])) {
                // Never needed from now on
                unset($args['always']);

                if (isset($args['tag'])) {
                    $tag = $args['tag'];
                    unset($args['tag']);
                } else {
                    $tag = 'div';
                }

                $source = array($this->menu->getParameterSource(), $this->request);

                if ($this->useBootstrap && !isset($args['tag'])) {
                    $div = \MUtil_Html::create('ol', $args + array('id' => 'crumbs', 'class' => 'breadcrumb'));

                    foreach ($path as $menuItem) {
                        $div->li()->a($menuItem->toHRefAttribute($source), $menuItem->get('label'));
                    }

                    if ($last) {
                        $div->li(array('class' => 'active'))->append($last->get('label'));
                    }

                } else {
                    $div = \MUtil_Html::create($tag, $args + array('id' => 'crumbs'));

                    $content = $div->seq();
                    $content->setGlue(\MUtil_Html::raw($this->_(' > ')));
                    // Add request to existing menu parameter sources

                    foreach ($path as $menuItem) {
                        $content->a($menuItem->toHRefAttribute($source), $menuItem->get('label'));
                    }

                    if ($last) {
                        $content->append($last->get('label'));
                    }
                }

                return $div;
            }
        }
    }

    /**
     * Function called if specified in the Project.ini layoutPrepare section before
     * the layout is drawn, but after the rest of the program has run it's course.
     *
     * @return mixed If null nothing is set, otherwise the name of
     * the function is used as \Zend_View variable name.
     */
    protected function _layoutCss()
    {
        // Set CSS stylescheet(s)
        $projectCss = isset($this->project->css) ? (array) $this->project->css : [];
        $projectCss = array_reverse($projectCss);

        foreach ($projectCss as $css) {
            if (is_array($css)) {
                $media = $css['media'];
                $url = $css['url'];
            } else {
                $url = $css;
                $media = 'all';
            }
            // When exporting to pdf, we need full urls
            if (substr($url,0,4) == 'http') {
                $this->view->headLink()->prependStylesheet($url, $media);
            } else {
                $this->view->headLink()->prependStylesheet($this->view->serverUrl() . $this->basepath->getBasePath() . '/' . $url, $media);
            }
        }
    }

    /**
     * Function called if specified in the Project.ini layoutPrepare section before
     * the layout is drawn, but after the rest of the program has run it's course.
     *
     * @return mixed If null nothing is set, otherwise the name of
     * the function is used as \Zend_View variable name.
     */
    protected function _layoutFavicon()
    {
        // FAVICON
        $icon = isset($this->project->favicon) ? $this->project->favicon : 'favicon.ico';
        if (file_exists(GEMS_WEB_DIR . '/' . $icon)) {
            $this->view->headLink(
                array(
                    'rel' => 'shortcut icon',
                    'href' =>  $this->basepath->getBasePath() . '/' . $icon,
                    'type' => 'image/x-icon'
                    ),
                \Zend_View_Helper_Placeholder_Container_Abstract::PREPEND
                );
        }
    }

    /**
     * Function called if specified in the Project.ini layoutPrepare section before
     * the layout is drawn, but after the rest of the program has run it's course.
     *
     * @return mixed If null nothing is set, otherwise the name of
     * the function is used as \Zend_View variable name.
     */
    protected function _layoutGroupSwitcher(array $args = null)
    {
        if ($this->currentUser->isActive() && $this->currentUser->hasPrivilege('pr.group.switch', false)) {
            $groups = $this->currentUser->getAllowedStaffGroups(false);

            if (count($groups) > 1) {
                // Group switcher
                return $this->getUiSwitcher($groups, $this->currentUser->getGroupId(), 'groups', 'group', 'group', $args);
            }
        }
    }

    /**
     * Function called if specified in the Project.ini layoutPrepare section before
     * the layout is drawn, but after the rest of the program has run it's course.
     *
     * @return mixed If null nothing is set, otherwise the name of
     * the function is used as \Zend_View variable name.
     */
    protected function _layoutJQuery()
    {
        // JQUERY
        if (\MUtil_JQuery::usesJQuery($this->view)) {
            $jquery = $this->view->jQuery();
            $jquery->uiEnable(); // enable user interface

            $jqueryCss = isset($this->project->jquerycss) ? (array) $this->project->jquerycss : [];

            foreach ($jqueryCss as $css) {
                $jquery->addStylesheet($this->basepath->getBasePath() . '/' . $css);
            }

            return true;
        }
    }

    /**
     * Function called if specified in the Project.ini layoutPrepare section before
     * the layout is drawn, but after the rest of the program has run it's course.
     *
     * @return mixed If null nothing is set, otherwise the name of
     * the function is used as \Zend_View variable name.
     */
    protected function _layoutLocaleSet(array $args = null)
    {
        // LOCALE
        $currentUri = base64_encode($this->view->url());
        $localeDiv = \MUtil_Html::create('div', $args, array('id' => 'languages'));

        // There will always be a localeDiv, but it can be empty
        if (isset($this->project->locales)) {
            foreach ($this->project->locales as $locale) {
                if ($locale == $this->view->locale) {
                    $localeDiv->span(strtoupper($locale), array('class' => 'language ' . $locale));
                } else {
                    $localeDiv->a(
                        array(
                            'controller' => 'language',
                            'action' => 'change-ui',
                            'language' => urlencode($locale),
                            'current_uri' => $currentUri,
                            'class' => ''                                
                        ),
                        strtoupper($locale),
                        array(
                            'class' => 'language ' . $locale, 
                            'rel' => 'nofollow'
                        )
                    );
                }
                $localeDiv[] = ' ';
            }
        }
        return $localeDiv;
    }

    /**
     * Display either a link to the login screen or displays the name of the current user
     * and a logoff link.
     *
     * Function called if specified in the Project.ini layoutPrepare section before
     * the layout is drawn, but after the rest of the program has run it's course.
     *
     * @return mixed If null nothing is set, otherwise the name of
     * the function is used as \Zend_View variable name.
     */
    protected function _layoutLogin(array $args = null)
    {
        // During error reporting the user or menu are not always known.
        if ($this->currentUser && $this->menu) {
            $div = \MUtil_Html::create('div', array('id' => 'login'), $args);

            $p = $div->p();
            if ($this->currentUser->isActive()) {
                $p->append(sprintf($this->_('You are logged in as %s'), $this->currentUser->getFullName()));
                $item = $this->menu->findController('index', 'logoff');
                $p->a($item->toHRefAttribute(), $this->_('Logoff'), array('class' => 'logout'));
                $item->set('visible', false);
            } else {
                $item = $this->menu->findController('index', 'login');
                $p->a($item->toHRefAttribute(), $this->_('You are not logged in'), array('class' => 'logout'));
            }

            return $div;
        }
    }

    /**
     * Function called if specified in the Project.ini layoutPrepare section before
     * the layout is drawn, but after the rest of the program has run it's course.
     *
     * @return mixed If null nothing is set, otherwise the name of
     * the function is used as \Zend_View variable name.
     */
    protected function _layoutMenuActiveBranch()
    {
        // ACL && Menu
        if ($this->menu && $this->menu->isVisible()) {
            return $this->menu->toActiveBranchElement();
        }
    }

    /**
     * Function called if specified in the Project.ini layoutPrepare section before
     * the layout is drawn, but after the rest of the program has run it's course.
     *
     * @return mixed If null nothing is set, otherwise the name of
     * the function is used as \Zend_View variable name.
     */
    protected function _layoutMenuHtml()
    {
        // ACL && Menu
        if ($this->menu && $this->menu->isVisible()) {

            // Make sure the actual $request and $controller in use at the end
            // of the dispatchloop is used and make \Zend_Navigation object
            return $this->menu->render($this->view);
        }
    }

    /**
     * Function called if specified in the Project.ini layoutPrepare section before
     * the layout is drawn, but after the rest of the program has run it's course.
     *
     * @return mixed If null nothing is set, otherwise the name of
     * the function is used as \Zend_View variable name.
     */
    protected function _layoutMenuTopLevel()
    {
        // ACL && Menu
        if ($this->menu && $this->menu->isVisible()) {
            return $this->menu->toTopLevelElement();
        }
    }

    /**
     * Function called if specified in the Project.ini layoutPrepare section before
     * the layout is drawn, but after the rest of the program has run it's course.
     *
     * @return mixed If null nothing is set, otherwise the name of
     * the function is used as \Zend_View variable name.
     */
    protected function _layoutMessages()
    {
        // Do not trust $messenger being set in the view,
        // after a reroute we have to reinitiate te $messenger.
        $messenger = $this->getMessenger();
        return $messenger->showMessages();
    }

    /**
     * Function called if specified in the Project.ini layoutPrepare section before
     * the layout is drawn, but after the rest of the program has run it's course.
     *
     * @return mixed If null nothing is set, otherwise the name of
     * the function is used as \Zend_View variable name.
     */
    protected function _layoutNavigation()
    {
        // ACL && Menu
        if ($this->menu && $this->menu->isVisible()) {

            // Make sure the actual $request and $controller in use at the end
            // of the dispatchloop is used and make \Zend_Navigation object
            $nav = $this->menu->toZendNavigation($this->request, $this->controller);

            // Set the navigation object
            \Zend_Registry::set('Zend_Navigation', $nav);

            $zendNav = $this->view->navigation();
            // $zendNav->setAcl($this->acl);  // Not needed with \Gems_Menu
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
     * the function is used as \Zend_View variable name.
     */
    protected function _layoutOrganizationSwitcher(array $args = null)
    {
        if ($this->currentUser->isActive() && ($orgs = $this->currentUser->getAllowedOrganizations())) {
            if (count($orgs) > 1) {
                // Organization switcher
                return $this->getUiSwitcher($orgs, $this->currentUser->getCurrentOrganizationId(), 'organizations', 'org', 'organization', $args);
            }
        }
    }

    /**
     * Function called if specified in the Project.ini layoutPrepare section before
     * the layout is drawn, but after the rest of the program has run it's course.
     *
     * @return mixed If null nothing is set, otherwise the name of
     * the function is used as \Zend_View variable name.
     */
    protected function _layoutProjectName(array $args = null)
    {
        if (isset($args['tagName'])) {
            $tagName = $args['tagName'];
            unset($args['tagName']);
        } else {
            $tagName = 'h1';
        }
        return \MUtil_Html::create($tagName, $this->project->name, $args);
    }

    /**
     * Function called if specified in the Project.ini layoutPrepare section before
     * the layout is drawn, but after the rest of the program has run it's course.
     *
     * @return mixed If null nothing is set, otherwise the name of
     * the function is used as \Zend_View variable name.
     */
    protected function _layoutTime(array $args = null)
    {
        return \MUtil_Html::create()->div(date('d-m-Y H:i:s'), $args, array('id' => 'time'));
    }

    /**
     * Function called if specified in the Project.ini layoutPrepare section before
     * the layout is drawn, but after the rest of the program has run it's course.
     *
     * @return mixed If null nothing is set, otherwise the name of
     * the function is used as \Zend_View variable name.
     */
    protected function _layoutTitle(array $args = null)
    {
        if (is_array($args) && array_key_exists('separator', $args)) {
            $separator = $args['separator'];
        } else {
            $separator = ' - ';
        }

        if ($this->controller instanceof \MUtil_Controller_Action) {
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
     * the function is used as \Zend_View variable name.
     */
    protected function _layoutUser(array $args = null)
    {
        if ($this->currentUser->isActive()) {
            return \MUtil_Html::create()->div(
                sprintf($this->_('User: %s'), $this->currentUser->getFullName()),
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
     * the function is used as \Zend_View variable name.
     */
    protected function _layoutVersion(array $args = null)
    {
        $div = \MUtil_Html::create()->div($args, array('id' => 'version'));
        if ($this->currentUser->isActive()) {
            $version = $this->loader->getVersions()->getVersion();
        } else {
            $version = $this->loader->getVersions()->getMainVersion();
        }
        if (($this->menu instanceof \Gems_Menu) &&
                ($item = $this->menu->findController('project-information', 'changelog')->toHRefAttribute())) {
            $link = \MUtil_Html::create()->a($version, $item);
        } else {
            $link = $version;
        }

        $this->view->currentVersion = $this->loader->getVersions()->getProjectVersion();

        $div->spaced($this->project->description, $this->translateAdapter->_('version'), $link);

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
        if (!$this->_copyDestinations) {
            return;
        }
        $names = (array) $name;

        foreach ($this->_copyDestinations as $object) {
            foreach ($names as $key) {
                $object->$key = $this->_container->$key;
            }
        }
    }

    /**
     * Adds one or more messages to the session based message store.
     *
     * @param mixed ...$messages Can be an array or multiple argements. Each sub element is a single message string
     * @return \MUtil_Controller_Action
     */
    public function addMessage()
    {
        $messages  = \MUtil_Ra::flatten(func_get_args());
        $messenger = $this->getMessenger();

        foreach ($messages as $message) {
            $messenger->addMessage($message);
        }

        return $this;
    }

    /**
     * Hook 2: Called in $this->run().
     *
     * This->init() has ran and the constructor has finisched so all _init{name} and application.ini
     * resources have been loaded. The code between the constructor and the call to $this->run() has
     * been executed in $this->run() has hooked $this as both a \Zend_Controller_Plugin and a
     * \Zend_Controller_Action_Helper.
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
        $this->_copyVariables($this->view);
    }


    /**
     * Hook 10: Called before the $controller->preDispatch() and $controller->{name}Action
     * methods have been called.
     *
     * Here you can change or check all values set in $controller->init(). All output echoed
     * here is captured for the output.
     *
     * Previous hook: controllerInit()
     * Actions since: $controller->init(); ob_start(); $controller->dispatch()
     * Actions after: $controller->preDispatch(); $controller->{name}Action(); $controller->postDispatch()
     * Next hook: controllerAfterAction()
     *
     * @param \Zend_Controller_Action $actionController
     * @return void
     */
    public function controllerBeforeAction(\Zend_Controller_Action $actionController = null)
    {
        // Test for Zend_Session::$_unitTestEnabled: db is not yet loaded in tests
        if (method_exists($actionController, 'getRespondent') && (!\Zend_Session::$_unitTestEnabled)) {
            $this->accesslog->logRequest($this->request, array(), null, $actionController->getRespondent());
        } else {
            $this->accesslog->logRequest($this->request, array());
        }
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
     * @param \Zend_Controller_Action $actionController
     * @return void
     */
    public function controllerInit(\Zend_Controller_Action $actionController = null)
    {
        $this->_copyVariables($actionController ? $actionController : $this->controllerAfterAction);

        $this->prepareController();

        $imgUrl = $this->getUtil()->getImageUri('datepicker.png');
        $jstUrl = $this->basepath->getBasePath() . '/gems/js';

        // Now set some defaults
        $dateFormOptions['dateFormat']   = 'dd-MM-yyyy';
        $dateFormOptions['description']  = $this->_('dd-mm-yyyy');
        $dateFormOptions['size']         =  10;

        if ($this->useBootstrap == true) {
            // Do not use a buttonImage, since we will use bootstrap add-on
            $basicOptions = array();
        } else {
            $basicOptions = array(
                'buttonImage' => $imgUrl,
                'showOn'      => 'button'
            );
        }

        $dateFormOptions['jQueryParams'] = $basicOptions + array(
            'changeMonth' => true,
            'changeYear'  => true,
            'duration'    => 'fast',
        );
        $datetimeFormOptions['dateFormat']   = 'dd-MM-yyyy HH:mm';
        $datetimeFormOptions['description']  = $this->_('dd-mm-yyyy hh:mm');
        $datetimeFormOptions['size']         = 16;
        $datetimeFormOptions['jQueryParams'] = $basicOptions + array(
            'changeMonth' => true,
            'changeYear'  => true,
            'duration'    => 'fast',
            'stepMinute'  => 5,
            'size'        => 8,
            'timeJsUrl'   => $jstUrl,
        );

        $timeFormOptions['dateFormat']   = 'HH:mm';
        $timeFormOptions['description']  = $this->_('hh:mm');
        $timeFormOptions['jQueryParams'] = $basicOptions + array(
            'duration'    => 'fast',
            'stepMinute'  => 5,
            'size'        => 8,
            'timeJsUrl'   => $jstUrl,
        );

        \MUtil_Model_Bridge_FormBridge::setFixedOptions(array(
            'date'     => $dateFormOptions,
            'datetime' => $datetimeFormOptions,
            'time'     => $timeFormOptions,
            ));
    }

    /**
     * Creates an object of the specified className seareching the loader dirs path
     *
     * @param string $className
     * @param mixed ...$arguments Optional parameters
     * @return object
     */
    protected function createProjectClass($className)
    {
        $arguments = func_get_args();
        array_shift($arguments);

        return $this->_projectLoader->createClass($className, $arguments);
    }

    /**
     * Hook 7: Called before \Zend_Controller_Front enters its dispatch loop.
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
     * @param  \Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function dispatchLoopStartup(\Zend_Controller_Request_Abstract $request)
    {
        // Check the installation
        if (! isset($this->db)) {
            $this->setException(new \Gems_Exception_Coding(
                    'No database registered in ' . GEMS_PROJECT_NAME . 'Application.ini for key resources.db.')
                    );
        }

        if (($request instanceof \Zend_Controller_Request_Http) && $request->isPost()) {
            $posts = $request->getPost();
        } else {
            $posts = [];
        }

        $this->_checkParameters($request->getParams(), $posts);

        // Empty params are filtered from request, only saved when using special router
        $router = Zend_Controller_Front::getInstance()->getRouter();
        if ($router instanceof Rewrite) {
            $this->_checkParameters($router->getAllParams(), $posts);
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
     *
     * @return int The current active organization id or 0 when not known
     * @deprecated Since 1.7.2 Replaced by $this->currentOrganization->getId();
     */
    public function getCurrentOrganization()
    {
        return $this->currentOrganization->getId();
    }

    /**
     *
     * @return int The current user id or 0 when not known.
     * @deprecated Since 1.7.2 Replaced by $this->currentUser->getUserId();
     */
    public function getCurrentUserId()
    {
        return $this->currentUser->getUserId();
    }

    /**
     * Return the directories where the Database Administrator Model (DbaModel)
     * should look for sql creation files.
     *
     * @return array Of index => array('path' =>, 'name' =>, 'db' =>,)
     */
    public function getDatabasePaths()
    {
        $path = APPLICATION_PATH . '/configs/db';
        if (file_exists($path)) {
            $paths[] = array(
                'path' => $path,
                'name' => GEMS_PROJECT_NAME,
                'db'   => $this->db,
                );
        }

        $path = GEMS_LIBRARY_DIR . '/configs/db';
        if (file_exists($path)) {
            $paths[] = array(
                'path' => $path,
                'name' => 'gems',
                'db'   => $this->db,
                );
        }

        if ($this->project->hasResponseDatabase()) {
            $path = GEMS_LIBRARY_DIR . '/configs/db_response_data';
            if (file_exists($path)) {
                $paths[] = array(
                    'path' => $path,
                    'name' => 'gemsdata',
                    'db'   => $this->project->getResponseDatabase(),
                    );
            }
        }

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
     * @return \Gems_Loader Or a subclassed version when specified in the project code
     */
    public function getLoader()
    {
        return $this->loader;
    }

    /**
     * The prefix / directory paths where the Gems Loaders should look
     *
     * @return array
     */
    public function getLoaderDirs()
    {
        return $this->_loaderDirs;
    }

    /**
     * Retrieves / sets the messenger
     *
     * @return \Zend_Controller_Action_Helper_FlashMessenger
     */
    public function getMessenger()
    {
        if (! isset($this->view->messenger)) {
            $this->view->messenger = $this->loader->getMessenger();
        }
        return $this->view->messenger;
    }

    /**
     *
     * @param array $items Array of id=>item
     * @param string $currentId Id of currently selected item
     * @param string $elementId Id to set on the returned element
     * @param string $elementName The name of the select element
     * @param string $controller The controller to redirect to
     * @param array $args Optional list of arguments
     * @param string $action Optional action
     * @return \MUTil_Html
     */
    protected function getUiSwitcher($items, $currentId, $elementId, $elementName, $controller, array $args = null, $action = 'change-ui')
    {
        $uiSwitch  = \MUtil_Html::create('div', $args, array('id' => $elementId));
        $params    = $this->request->getparams();
        unset($params['error_handler']);    // If present, this is an object and causes a warning
        unset($params[\MUtil_Model::AUTOSEARCH_RESET]);
        if ($this->request instanceof \Zend_Controller_Request_Http) {
            // Use only get params, not post as it is an url
            $params = array_diff_key($params, $this->request->getPost());
        }

        $currentUri = $this->view->url($params, null, true);
        $url        = $this->view->url(array('controller' => $controller, 'action' => $action), null, false);

        $formDiv = $uiSwitch->form(array('method' => 'get', 'action' => $url))->div();
        $formDiv->input([
            'type'  => "hidden",
            'name'  => "current_uri",
            'value' => base64_encode($currentUri)
        ]);

        $select = $formDiv->select([
            'class'    => 'form-control',
            'name'     => $elementName,
            'onchange' => "javascript:this.form.submit();",
        ]);
        foreach ($items as $elementId => $name) {
            $selected = '';
            if ($elementId == $currentId) {
                $selected = array('selected' => "selected");
            }
            $select->option(array('value' => $elementId), $name, $selected);
        }

        return $uiSwitch;
    }

    /**
     * Type access to $this->util
     *
     * @return \Gems_Util Or a subclassed version when specified in the project code
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
     * @deprecated Since 1.7.2 Replaced by $this->currentUser->hasPrivilege();
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
                    $config = new \Zend_Config_Ini($fileName, APPLICATION_ENV);
                    break;

                case 'xml':
                    $config = new \Zend_Config_Xml($fileName, APPLICATION_ENV);
                    break;

                case 'php':
                case 'inc':
                    // Exclude all variables not needed
                    unset($extension);

                    // All variables from this Escort file can be changed in the include file.
                    return include($fileName);
                    break;

                default:
                    throw new \Zend_Application_Exception(
                            'Invalid configuration file provided; unknown config type ' . $extension
                            );

            }

            return $config->toArray();
        }

        // If the file does not exists it is up to the calling function to do something about it.
        return false;
    }

    /**
     * Is the host name one allowed by the system
     *
     * @param string $fullHost
     * @return boolean
     */
    public function isAllowedHost($fullHost)
    {
        $host = \MUtil_String::stripToHost($fullHost);
        $request = $this->request;
        if ($request instanceof \Zend_Controller_Request_Http) {
            if ($host == \MUtil_String::stripToHost($request->getServer('HTTP_HOST'))) {
                return true;
            }
        }
        if (isset($this->project)) {
            foreach ($this->project->getAllowedHosts() as $allowedHost) {
                if ($host == \MUtil_String::stripToHost($allowedHost)) {
                    return true;
                }
            }
        }
        $loader = $this->getLoader();
        foreach ($loader->getUserLoader()->getOrganizationUrls() as $url => $orgId) {
            if ($host == \MUtil_String::stripToHost($url)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return a hashed version of the input value.
     *
     * @deprecated Since 1.5
     *
     * @param string $value The value to hash.
     * @param boolean $new Optional is new, is here for ModelAbstract setOnSave compatibility
     * @param string $name Optional name, is here for ModelAbstract setOnSave compatibility
     * @param array $context Optional, the other values being saved
     * @return string The salted hash as a 32-character hexadecimal number.
     */
    public function passwordHash($value, $isNew = false, $name = null, array $context = array())
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
     * Returns a static session, that will not be affected by loading or unloading a user
     *
     * @return \Zend_Session_Namespace
     */
    public function getStaticSession()
    {
        return $this->staticSession;
    }

    /**
     * Hook 12: Called after an action is dispatched by \Zend_Controller_Dispatcher.
     *
     * This callback allows for proxy or filter behavior. By altering the
     * request and resetting its dispatched flag (via {@link
     * \Zend_Controller_Request_Abstract::setDispatched() setDispatched(false)}),
     * a new action may be specified for dispatching.
     *
     * \Zend_Layout_Controller_Plugin_Layout uses this event to change the output
     * of the $response with the rendering of the layout. As the Layout plugin
     * has a priority of 99, this Escort event will take place before the layout
     * is rendered, unless $this->run() was called with a stackIndex lower than zero.
     *
     * Previous hook: controllerAfterAction()
     * Actions since: ob_get_clean(); $response->appendBody()
     * Actions after: while (! Request->isDispatched()) or back to Hook 8 preDispatch()
     * Next hook: dispatchLoopShutdown()
     *
     * @param  \Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function postDispatch(\Zend_Controller_Request_Abstract $request)
    {
        if ($request->isDispatched()) {
            $headers = $this->project->getResponseHeaders();
            foreach ($headers as $name => $value) {
                $this->response->setHeader($name, $value, true);
            }

            // Only when we need to render the layout, we run the layout prepare
            if (\Zend_Controller_Action_HelperBroker::hasHelper('layout') &&
                    \Zend_Controller_Action_HelperBroker::getExistingHelper('layout')->isEnabled()) {

                // Per project layout preparation
                $layoutFuncs = isset($this->project->layoutPrepare) ? $this->project->layoutPrepare : [];
                $layoutArgs  = isset($this->project->layoutPrepareArgs) ? $this->project->layoutPrepareArgs : [];
                $this->prepareLayout($layoutFuncs, $layoutArgs);
            }

            // For AJAX calls we sometimes need to add JQuery onload scripts since otherwise they won't get rendered:
            // We expect JQuery to be loaded in the master page, since the call is probably made using JQuery
            if ($request instanceof \Zend_Controller_Request_Http && $request->isXmlHttpRequest()) {
                \MUtil_JQuery::enableView($this->view);
                $scripts = $this->view->jQuery()->getOnLoadActions();
                $content = '';
                foreach($scripts as $script) {
                    $content .= "<script type='text/javascript'>$script</script>\n";
                }
                $content .= $this->view->inlineScript();

                // Now cleanup the rendered content (just to make sure)
                $this->view->jQuery()->clearOnLoadActions();
                $this->view->inlineScript()->exchangeArray(array());
                if (!empty($content)) {
                    $this->response->appendBody($content);
                }
            }
        }
    }

    /**
     * Copy from \Zend_Translate_Adapter
     *
     * Translates the given string using plural notations
     * Returns the translated string
     *
     * @see \Zend_Locale
     * @param  string             $singular Singular translation string
     * @param  string             $plural   Plural translation string
     * @param  integer            $number   Number for detecting the correct plural
     * @param  string|\Zend_Locale $locale   (Optional) Locale/Language to use, identical with
     *                                      locale identifier, @see \Zend_Locale for more information
     * @return string
     */
    public function plural($singular, $plural, $number, $locale = null)
    {
        $args = func_get_args();
        return call_user_func_array(array($this->translateAdapter, 'plural'), $args);
    }

    /**
     * Hook 8: Start of dispatchLoop. Called before an action is dispatched
     * by \Zend_Controller_Dispatcher.
     *
     * This callback allows for proxy or filter behavior. By altering the request
     * and resetting its dispatched flag (via {@link \Zend_Controller_Request_Abstract::setDispatched()
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
     * @param  \Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function preDispatch(\Zend_Controller_Request_Abstract $request)
    {
        if ($request instanceof \Zend_Controller_Request_Http && $request->isXmlHttpRequest()) {
            $mvc = \Zend_Layout::getMvcInstance();

            if ($mvc instanceof \Zend_Layout) {
                $mvc->disableLayout();
            }
        }
        $staticSession = $this->getStaticSession();
        if ($this->session->user_id && $previousRequestParameters = $staticSession->previousRequestParameters) {
            unset($previousRequestParameters['save_button']);
            unset($previousRequestParameters['userlogin']);
            unset($previousRequestParameters['password']);

            // fake POST
            if ($staticSession->previousRequestMode == 'POST') {
                $this->addMessage($this->_('Take note: your session has expired, your inputs were not saved. Please check the input data and try again'));
                $_POST = $previousRequestParameters;
                $_SERVER['REQUEST_METHOD'] = $staticSession->previousRequestMode;
                $staticSession->previousRequestMode = null;
            }

            $staticSession->previousRequestParameters = null;
        }

        $this->setControllerDirectory($request);
    }

    /**
     * Hook function called during controllerInit
     *
     * return @void
     */
    public function prepareController()
    {
        // Do the layout switch here, when view is set the layout can still be changed, but
        // Bootstrap can no longer be switched on/off
        if ($this instanceof \Gems_Project_Layout_MultiLayoutInterface) {
            $this->layoutSwitch();
        }

        if ($this->useBootstrap) {
            $bootstrap = \MUtil_Bootstrap::bootstrap(array('fontawesome' => true));
            \MUtil_Bootstrap::enableView($this->view);
        }

        if (\MUtil_Console::isConsole()) {
            /* @var $layout \Zend_Layout */
            $layout = $this->view->layout();

            $layout->setLayoutPath(GEMS_LIBRARY_DIR . "/layouts/scripts");
            $layout->setLayout('cli');
        }
    }

    /**
     *
     * @param array $layoutFuncs
     * @param array $layoutArgs
     */
    protected function prepareLayout($layoutFuncs, $layoutArgs)
    {
        foreach ($layoutFuncs as $prepare => $type) {
            if (!$type) {
                continue;
            }
            $function = '_layout' . ucfirst($prepare);
            $args     = isset($layoutArgs[$prepare]) ? $layoutArgs[$prepare] : [];
            $result   = $this->$function($args);

            // When a result is returned, add it to the view,
            // according to the type method
            if (is_null($result)) {
                continue;
            }
            if (is_numeric($type)) {
                $this->view->$prepare = $result;
            } else {
                if (!isset($this->view->$type)) {
                    $this->view->$type = new \MUtil_Html_Sequence();
                }
                $sequence           = $this->view->$type;
                $sequence[$prepare] = $result;
            }
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
     * @param \Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function requestChanged(\Zend_Controller_Request_Abstract $request)
    {
        if ($this->project->isMultiLocale()) {
            // Get the choosen language
            $localeId = \Gems_Cookies::getLocale($request);

            // Change when $localeId exists and is different from session
            if ($localeId && ($this->locale->getLanguage() !== $localeId)) {
                // \MUtil_Echo::r('On cookie ' . $localeId . ' <> ' . $this->locale->getLanguage());

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
                    $this->translateAdapter = $this->translate->getAdapter();
                }
            }
        }

        // Set the base path, the route is now fixed
        $this->basepath->setBasePath($request->getBasePath());

        // Set the jQuery version and other information needed
        // by classes using jQuery
        $jquery = \MUtil_JQuery::jQuery();

        $jqueryVersion   = '1.11.1';
        $jqueryUiVersion = '1.11.1';
        $jquery->setVersion($jqueryVersion);
        $jquery->setUiVersion($jqueryUiVersion);

        if ($this->project->isJQueryLocal()) {
            $jqueryDir = $request->getBasePath() . $this->project->getJQueryLocal();

            $jquery->setLocalPath($jqueryDir . 'jquery-' . $jqueryVersion . '.js');
            $jquery->setUiLocalPath($jqueryDir . 'jquery-ui-' . $jqueryUiVersion . '.js');

        } else {
            if (\MUtil_Https::on()) {
                $jquery->setCdnSsl(true);
            }
        }
        if (\MUtil_Bootstrap::enabled() && $this->project->isBootstrapLocal()) {
            $bootstrap = \MUtil_Bootstrap::bootstrap();
            $basePath = $request->getBasePath();
            $bootstrap->setBootstrapScriptPath($basePath.'/bootstrap/js/bootstrap.min.js');
            $bootstrap->setBootstrapStylePath($basePath.'/bootstrap/css/bootstrap.min.css');
            $bootstrap->setFontAwesomeStylePath($basePath.'/bootstrap/css/font-awesome.min.css');
        }
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
    public function responseChanged(\Zend_Controller_Response_Abstract $response)
    {
        $response->setHeader('Expires', '', true);
    }

    /**
     * Hook 6: Called after \Zend_Controller_Router has determined the route set by the request.
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
     * Also sets $this->currentOrganization and $this->menu to access afterwards
     *
     * @param  \Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function routeShutdown(\Zend_Controller_Request_Abstract $request)
    {
        $loader = $this->getLoader();

        // Load the menu. As building the menu can depend on all resources and the request, we do it here.
        //
        // PS: The REQUEST is needed because otherwise the locale for translate is not certain.
        $menu   = $loader->createMenu($this);
        $source = $menu->getParameterSource();
        $user   = $this->_container->currentUser;
        $user->setRequest($request);

        $action       = $request->getActionName();
        $controller   = $request->getControllerName();
        $organization = $user->getCurrentOrganization();
        $organization->applyToMenuSource($source);

        $this->_container->currentOrganization = $organization;
        $this->_container->menu = $menu;
        $this->_updateVariable(array('currentOrganization', 'menu'));

        // Now is a good time to check for required values
        // Moved down here to prevent unit test from failing on missing salt
        $this->project->checkRequiredValues();

        /**
         * Check if we are in maintenance mode or not. This is triggeren by a file in the var/settings
         * directory with the name lock.txt
         */
        if ($this->getUtil()->getMaintenanceLock()->isLocked()) {
            if ($user->hasPrivilege('pr.maintenance.maintenance-mode', false)) {
                \MUtil_Echo::r($this->_('System is in maintenance mode'));

            } elseif (! (isset($this->maintenanceAccessiblePages[$controller]) &&
                    is_array($this->maintenanceAccessiblePages[$controller]) &&
                    in_array($action, $this->maintenanceAccessiblePages[$controller]))) {

                $this->addMessage($this->_('The page you requested is currently inaccessible.'));
                $this->setError($this->_('Please check back later.'), 401);

                $user->unsetAsCurrentUser();
            }
            $this->addMessage($this->_('System is in maintenance mode'));
        }

        // Gems does not use index/index
        if (('index' == $controller) &&
                (('index' == $action) || ($user->isActive() && ('login' == $action)))) {
            // Instead Gems routes to the first available menu item when this is the request target
            if (! $user->gotoStartPage($menu, $request)) {
                $this->setError(
                        $this->_('No access to site.'),
                        401,
                        $this->_('You have no access to this site.'),
                        true);
                return;
            }

        } else {
            //find first allowed item in the menu
            $menuItem = $menu->find(['action' => $action, 'controller' => $controller]);

            // Display error when not having the right priviliges
            if (! ($menuItem && $menuItem->get('allowed'))) {
                // When logged in
                if ($user->getUserId()) {
                    $this->setError(
                            $this->_('No access to page'),
                            403,
                            sprintf($this->_('Access to the %s/%s page is not allowed for your current group: %s.'),
                                    $controller,
                                    $action,
                                    $user->getGroup()->getName()),
                            true);

                } else { // No longer logged in

                    if (\MUtil_Console::isConsole()) {
                        $this->setError(
                                'No access to page.',
                                401,
                                sprintf('Controller "%s" action "%s" is not accessible.', $controller, $action),
                                true);
                        return;
                    }

                    if ($action == 'autofilter') {
                        // Throw an exception + HTTP 401 when an autofilter is called
                        throw new \Gems_Exception("Session expired", 401);
                    }
                    $menuItem = $menu->findFirst(['allowed' => true, 'visible' => true]);
                    if ($menuItem) {
                        // Do not store previous request & show message when the intended action is logoff
                        if (! ($controller == 'index' && $action == 'logoff')) {
                            $this->addMessage($this->_('You are no longer logged in.'));
                            $this->addMessage($this->_('You must login to access this page.'));

                            if (! \MUtil_String::contains($controller . $action, '.')) {
                                // save original request, we will redirect back once the user succesfully logs in
                                $staticSession = $this->getStaticSession();
                                $staticSession->previousRequestParameters = $request->getParams();
                                $staticSession->previousRequestMode = ($request->isPost() ? "POST" : "GET");
                            }
                        }

                        $redirector = \Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
                        $redirector->gotoRoute($menuItem->toRouteUrl($request));

                    } else {
                        $this->setError(
                                $this->_('You are no longer logged in.'),
                                401,
                                $this->_('You have no access to this site.'),
                                true);
                        return;
                    }
                }
            }
        }

        if (isset($menuItem)) {
            $menuItem->applyHiddenParameters($request, $source);
            $menu->setCurrent($menuItem);
        }
        if ($request instanceof \Zend_Controller_Request_Http) {
            if ($request->isPost()) {
                $incoming = $request->getServer('HTTP_ORIGIN', $request->getServer('HTTP_REFERER', false));
                if ($incoming) {
                    if (! $this->isAllowedHost($incoming)) {
                        throw new \Gems_Exception(
                            sprintf("Invalid source host, possible CSRF attack. Used host: %s", $incoming),
                            403
                        );
                    }
                }
            }
        }
    }

    public function setControllerDirectory(\Zend_Controller_Request_Abstract $request)
    {
        // Set Controller directory within dispatch loop to handle forwards and exceptions
        $module              = $request->getModuleName();
        $front               = $this->frontController;
        $controllerFileName  = $front->getDispatcher()->getControllerClass($request) . '.php';

        // \MUtil_Echo::r(APPLICATION_PATH . '/controllers/' . $controllerFileName);

        // Set to project path if that controller exists
        // TODO: Dirs & modules combineren.
        if (file_exists(APPLICATION_PATH . '/controllers/' . $controllerFileName)) {
            $front->setControllerDirectory(APPLICATION_PATH . '/controllers', $module);
        } else {
            $front->setControllerDirectory(GEMS_LIBRARY_DIR . '/controllers', $module);
        }
    }

    /**
     * Create an exception for the error, depending on processing position we either
     * set the response exception or throw the exception if the response is
     *
     * @param string $message
     * @param int $code
     * @param string $info
     * @param boolean $isSecurity
     * @throws exception
     */
    public function setError($message, $code = 200, $info = null, $isSecurity = false)
    {
        if ($isSecurity) {
            $e = new \Gems_Exception_Security($message, $code, null, $info);
        } else {
            $e = new \Gems_Exception($message, $code, null, $info);
        }
        $this->setException($e);
    }

    /**
     * Handle the exception depending on processing position we either
     * set the response exception or throw the exception if the response is
     *
     * @param exception $e
     * @throws exception
     */
    public function setException(exception $e)
    {
        if (isset($this->response)) {
            $this->response->setException($e);
        } else {
            throw $e;
        }
    }
}