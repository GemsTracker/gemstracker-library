<?php

declare(strict_types=1);


namespace Gems\Legacy;

use Gems\View\View;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use Zalt\Loader\ProjectOverloader;

class LegacyFactory implements FactoryInterface
{

    protected ContainerInterface $container;

    protected array $config;

    protected $init;

    protected ProjectOverloader $loader;

    public function __invoke(ContainerInterface $container, $requestedName, ?array $options = null): ?object
    {
        $this->container = $container;
        $this->config = $container->get('config');
        $this->loader = $container->get(ProjectOverloader::class);

        $this->init();

        switch ($requestedName) {
            case \Gems\Loader::class:
            case \Gems\Util::class:
            case \Gems\Tracker::class:
            case \Gems\Tracker\TrackEvents::class:
            case \Gems\Agenda::class:
            case \Gems\Model::class:
            case \Gems\Menu::class:
            case \Gems\User\UserLoader::class:
                $requestedName = $this->stripOverloader($requestedName);
                return $this->loader->create($requestedName, $this->loader, []);

            case 'LegacyCurrentUser':
                return $this->getCurrentUser();

            case \Zend_Acl::class:
                return $this->getAcl();

            case \Gems\Project\ProjectSettings::class:
                $project = $this->getProjectSettings();
                return $project;

            case \Gems\Util\BasePath::class:
                $requestedName = $this->stripOverloader($requestedName);
                $basePath = $this->loader->create($requestedName, $this->loader, []);
                $basePath->setBasePath('/');
                return $basePath;

            case \Zend_Locale::class:
                $locale = $this->getLocale();
                return $locale;

            case \Zend_Session_Namespace::class:
                return $this->getSession();

            case \Zend_Translate::class:
                //$translateOptions = $this->getTranslateOptions();
                return $this->getTranslate();
                break;

            case \Zend_Translate_Adapter::class:
                return $this->getTranslateAdapter();
                break;
            case \Zend_View::class:
                return $this->getView();
        }

        return null;
    }

    private function findExtension(string $fullFileName, array $extensions): ?string
    {
        foreach ($extensions as $extension) {
            if (file_exists($fullFileName . '.' . $extension)) {
                return $extension;
            }
        }
        return null;
    }

    protected function getAcl()
    {
        //$roles = $this->loader->create('Roles', $cache, $logger);
        try {
            $roles = $this->loader->create('Roles', $this->container->get(CacheItemPoolInterface::class), $this->container->get('LegacyLogger'));
        } catch (\Gems\Exception $e) {
        }

        return $roles->getAcl();
    }

    public function getCurrentUser()
    {
        $currentUserRepository = $this->container->get(CurrentUserRepository::class);
        //try {
        $currentUser = $currentUserRepository->getCurrentUser();
        return $currentUser;
        /*} catch(\Exception $e) {
            return null;
        }*/
    }

    protected function getEnvironment(): string
    {
        if (defined('APPLICATION_ENV')) {
            return APPLICATION_ENV;
        } elseif ($config = $this->container->get('config') && isset($config['project'], $config['project']['environment'])) {
            return $config['project']['environment'];
        } elseif ($env = getenv('APPLICATION_ENV')) {
            return $env;
        }

        return 'development';
    }

    protected function getEventDispatcher()
    {
        $event = new EventDispatcher();
        if (isset($this->config['events'])) {
            foreach($this->config['events'] as $subscriberClass) {
                if ($this->container->has($subscriberClass)) {
                    $subscriber = $this->container->get($subscriberClass);
                } else {
                    $subscriber = new $subscriberClass;
                }
                $event->addSubscriber($subscriber);
            }
        }
        return $event;
    }

    protected function getLocale(): \Zend_Locale
    {
        $locale = new \Zend_Locale('default');
        \Zend_Registry::set('Zend_Locale', $locale);

        return $locale;
    }

    protected function getProjectSettings(): object
    {
        if (isset($this->config['db'], $this->config['db']['database'])) {
            defined('DATABASE') || define('DATABASE', $this->config['db']['database']);
        }

        $projectArray = $this->includeFile(GEMS_ROOT_DIR . '/config/project');

        $project = $this->loader->create('Project\\ProjectSettings', $projectArray);

        /* Testing if the supplied projectSettings is a class is supported in Gemstracker, but not used. For now it's disabled.
        /*if ($projectArray instanceof \Gems\Project\ProjectSettings) {
            $project = $projectArray;
        } else {
            $project = $this->loader->create('Project\\ProjectSettings', $projectArray);
        }*/

        return $project;
    }

    protected function getSession()
    {
        $project = $this->container->get('LegacyProject');
        $session = new \Zend_Session_Namespace('gems.' . GEMS_PROJECT_NAME . '.session');

        $idleTimeout = $project->getSessionTimeOut();

        $session->setExpirationSeconds($idleTimeout);

        if (! isset($session->user_role)) {
            $session->user_role = 'nologin';
        }

        return $session;
    }

    protected function getTranslate(): \Zend_Translate
    {
        $locale = $this->container->get('LegacyLocale');
        $language = $locale->getLanguage();

        /*
         * Scan for files with -<languagecode> and disable notices when the requested
         * language is not found
         */
        $options = [
            'adapter'         => 'gettext',
            //'content'         => GEMS_LIBRARY_DIR . '/languages/',
            'disableNotices'  => true,
            'scan'            => \Zend_Translate::LOCALE_FILENAME
        ];

        $translate = new \Zend_Translate($options);
        // If we don't find the needed language, use a fake translator to disable notices
        if (! $translate->isAvailable($language)) {
            $translate = \MUtil\Translate\Adapter\Potemkin::create();
        }

        $translate->setLocale($language);
        \Zend_Registry::set('Zend_Translate', $translate);

        return $translate;
    }

    protected function getTranslateAdapter()
    {
        $translate = $this->container->get(\Zend_Translate::class);

        return $translate->getAdapter();
    }


    protected function getView(): \Zend_View
    {
        $project = $this->container->get('LegacyProject');

        // Initialize view
        $view = new View();

        $prefix     = 'Zend_View_';
        $pathPrefix = 'Zend/View/';
        
        $loader = new \MUtil\Loader\PluginLoader([
            'Zend_View_Helper_' => 'Zend/View/Helper/',
            'MUtil_View_Helper_' => 'MUtil/View/Helper/',
            'Gems_View_Helper_' => 'Gems/View/Helper/',
            ]);
        $view->setPluginLoader($loader, 'helper');
        
        $view->headTitle($project->getName());
        $view->setEncoding('UTF-8');

        $metas    = $project->getMetaHeaders();
        $headMeta = $view->headMeta();
        foreach ($metas as $httpEquiv => $content) {
            $headMeta->appendHttpEquiv($httpEquiv, $content, []);
        }

        $view->doctype(\Zend_View_Helper_Doctype::HTML5);

        // Add it to the ViewRenderer
        $viewRenderer = \Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');
        $viewRenderer->setView($view);

        // Return it, so that it can be stored by the bootstrap
        return $view;
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
    protected function includeFile($fileName): ?array
    {
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);

        if (!$extension) {
            $extension = $this->findExtension($fileName, array('inc', 'ini', 'php', 'xml'));
            $fileName .= '.' . $extension;
        }

        if (file_exists($fileName)) {
            $appEnvironment = $this->getEnvironment();
            switch ($extension) {
                case 'ini':
                    $config = new \Zend_Config_Ini($fileName, $appEnvironment);
                    return $config->toArray();
                    break;

                /*case 'xml':
                    $config = new \Zend_Config_Xml($fileName, $appEnvironment);
                    return $config->toArray();
                    break;*/

                case 'php':
                case 'inc':
                    // Exclude all variables not needed
                    unset($extension);

                    // All variables from this Escort file can be changed in the include file.
                    return include($fileName);
                    break;
            }
        }

        return null;
    }

    protected function init(): void
    {
        if (!$this->init) {
            defined('GEMS_ROOT_DIR') || define('GEMS_ROOT_DIR', dirname(dirname(dirname(__FILE__))));
            defined('VENDOR_DIR') || define('VENDOR_DIR', GEMS_ROOT_DIR . '/vendor/');

            defined('GEMS_LIBRARY_DIR') || define('GEMS_LIBRARY_DIR', VENDOR_DIR . '/gemstracker/gemstracker');
            defined('MUTIL_LIBRARY_DIR') || define('MUTIL_LIBRARY_DIR', realpath(VENDOR_DIR . '/magnafacta/mutil/src'));

            if (!defined('APPLICATION_PATH')) {
                if (isset($this->config['project'], $this->config['project']['vendor'])) {
                    define('APPLICATION_PATH', VENDOR_DIR . $this->config['project']['vendor'] . '/application');
                } else {
                    define('APPLICATION_PATH', null);
                }
            }

            if (!defined('GEMS_PROJECT_NAME')) {
                if (isset($this->config['project'], $this->config['project']['name'])) {
                    define('GEMS_PROJECT_NAME', $this->config['project']['name']);
                } else {
                    define('GEMS_PROJECT_NAME', 'NewProject');
                }

            }
            defined('GEMS_PROJECT_NAME_UC') || define('GEMS_PROJECT_NAME_UC', ucfirst(GEMS_PROJECT_NAME));

            defined('APPLICATION_ENV') || define('APPLICATION_ENV', $this->getEnvironment());

            $this->init = true;
        }
    }

    protected function stripOverloader($requestedName)
    {
        $overloaders = $this->loader->getOverloaders();
        foreach($overloaders as $overloader) {
            if (strpos($requestedName, $overloader) === 0 || strpos($requestedName, '\\'.$overloader) === 0) {
                $requestedName = str_replace([$overloader.'_', $overloader.'\\', $overloader], '', $requestedName);
                return $requestedName;
            }
        }

        return $requestedName;
    }
}
