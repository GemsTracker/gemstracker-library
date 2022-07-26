<?php

/**
 * This files contains general project code that set constants and initializes
 * PHP for use with Zend / Gemstracker. Next the autoloader and \Zend_Application
 * are created and the bootstrap is started.
 *
 * @author Matijs de Jong <mjong@magnafacta.nl>
 * @since 1.0
 * @package Gems
 * @subpackage Project
 */

// PHP ENCODING SETUP
defined('APPLICATION_ENCODING') || define('APPLICATION_ENCODING', 'UTF-8');

mb_internal_encoding(APPLICATION_ENCODING);

/**
 *  Define path to application directory and UC project name if it does not yet exist
 */
defined('APPLICATION_PATH') || define('APPLICATION_PATH', GEMS_ROOT_DIR . '/application');
defined('GEMS_PROJECT_NAME_UC') || define('GEMS_PROJECT_NAME_UC', ucfirst(GEMS_PROJECT_NAME));
defined('GEMS_WEB_DIR') || define('GEMS_WEB_DIR', GEMS_ROOT_DIR . '/htdocs');

defined('VENDOR_DIR') || define('VENDOR_DIR', realpath(GEMS_ROOT_DIR . '/vendor/'));
defined('GEMS_LIBRARY_DIR') || define('GEMS_LIBRARY_DIR', realpath(VENDOR_DIR . '/gemstracker/gemstracker'));
defined('MUTIL_LIBRARY_DIR') || define('MUTIL_LIBRARY_DIR', realpath(VENDOR_DIR . '/magnafacta/mutil/src'));

/**
 * Use the composer autoloader, since we store this variable in global scope, projects can interact with it when needed.
 *
 * Composer autoloader takes care of adding to the include path
 */
$composer_autoloader = require (VENDOR_DIR . '/autoload.php');

/**
 * Just in case the project forget to add it's own path to the include path, we
 * add it here for backward compatibility
 */
set_include_path(
        APPLICATION_PATH . '/classes' . PATH_SEPARATOR .
        get_include_path()
);


// Create application, bootstrap, and run
$application = new \Zend_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/configs/application.ini'
);

// \MUtil\Model::$verbose = true;

$application->bootstrap()
            ->run();