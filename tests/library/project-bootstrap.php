<?php
/*
 * This is a helper bootstrap file so projects need only to set their project name using
 * define('GEMS_PROJECT_NAME', 'ProjectName');
 * and additional settings in their own bootstrap file and then can include this file for
 * the common settings
 */

/**
 * Setup environment
 */
defined('GEMS_WEB_DIR') || define('GEMS_WEB_DIR', dirname(dirname(__DIR__)));
defined('GEMS_ROOT_DIR') || define('GEMS_ROOT_DIR', dirname(GEMS_WEB_DIR));

defined('GEMS_PROJECT_NAME_UC') || define('GEMS_PROJECT_NAME_UC', ucfirst(GEMS_PROJECT_NAME));
defined('APPLICATION_ENV') || define('APPLICATION_ENV', 'development');

defined('APPLICATION_PATH') || define('APPLICATION_PATH', GEMS_ROOT_DIR . '/application');
defined('APPLICATION_PATH') || define('APPLICATION_PATH', GEMS_ROOT_DIR . '/application');
defined('GEMS_PROJECT_NAME_UC') || define('GEMS_PROJECT_NAME_UC', ucfirst(GEMS_PROJECT_NAME));

defined('VENDOR_DIR') || define('VENDOR_DIR', realpath(GEMS_ROOT_DIR . '/vendor/'));
defined('GEMS_LIBRARY_DIR') || define('GEMS_LIBRARY_DIR', realpath(VENDOR_DIR . '/gemstracker/gemstracker'));
defined('MUTIL_LIBRARY_DIR') || define('MUTIL_LIBRARY_DIR', realpath(VENDOR_DIR . '/magnafacta/mutil/src'));

defined('GEMS_TEST_DIR') || define('GEMS_TEST_DIR', GEMS_LIBRARY_DIR . '/tests');

// Make sure session save path is writable for current user (needed for Jenkins)
if (!is_writable( session_save_path())) {
     session_save_path(GEMS_TEST_DIR . '/tmp');
}

/**
 * Setup include path
 */
set_include_path(
    GEMS_TEST_DIR . '/classes' . PATH_SEPARATOR .
    GEMS_TEST_DIR . '/library' . PATH_SEPARATOR .
    GEMS_LIBRARY_DIR . '/classes' . PATH_SEPARATOR .
    MUTIL_LIBRARY_DIR . '/src');

// Set up autoload.
if (file_exists(VENDOR_DIR  . '/autoload.php')) {
    require_once VENDOR_DIR  . '/autoload.php';
} else {
    // Try to set the correct include path (if needed)
    $paths = array(
        'magnafacta/mutil/src',
        'magnafacta/mutil/tests',
        'zendframework/zendframework1/library',
        'zendframework/zf1-extras/library',
    );
    $start = VENDOR_DIR;
    foreach ($paths as $path) {
        $dir = realpath($start . $path);

        if (file_exists($dir) && (false===strpos(get_include_path(), $dir))) {
            set_include_path($dir . PATH_SEPARATOR . get_include_path());
        }
    }
    require_once "Zend/Loader/Autoloader.php";

    $autoloader = \Zend_Loader_Autoloader::getInstance();
    $autoloader->registerNamespace('MUtil_');
    $autoloader->registerNamespace('Gems_');

    // Otherwise not loaded by Zend Autoloader
    require_once "Gems/Tracker/Field/FieldInterface.php";
    require_once "Gems/Tracker/Field/FieldAbstract.php";
}

\Zend_Session::start();
\Zend_Session::$_unitTestEnabled = true;
