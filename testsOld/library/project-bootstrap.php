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
defined('APPLICATION_ENV')  || define('APPLICATION_ENV', 'development');
defined('APPLICATION_PATH') || define('APPLICATION_PATH', GEMS_ROOT_DIR . '/application');

defined('VENDOR_DIR')        || define('VENDOR_DIR', GEMS_ROOT_DIR . '/vendor/');
defined('MUTIL_LIBRARY_DIR') || define('MUTIL_LIBRARY_DIR', realpath(VENDOR_DIR . '/magnafacta'));

defined('GEMS_TEST_DIR')        || define('GEMS_TEST_DIR', realpath(__DIR__ . '/..'));
defined('GEMS_LIBRARY_DIR')     || define('GEMS_LIBRARY_DIR', VENDOR_DIR . '/gemstracker/gemstracker');
defined('GEMS_WEB_DIR')         || define('GEMS_WEB_DIR', GEMS_ROOT_DIR . '/htdocs');
defined('GEMS_PROJECT_NAME_UC') || define('GEMS_PROJECT_NAME_UC', ucfirst(GEMS_PROJECT_NAME));

// Make sure session save path is writable for current user (needed for Jenkins)
if (!is_writable( session_save_path())) {
     session_save_path(GEMS_TEST_DIR . '/tmp');
}

/**
 * Setup include path
 */
set_include_path(
    GEMS_ROOT_DIR . '/test/classes' . PATH_SEPARATOR .
    GEMS_ROOT_DIR . '/application/classes' . PATH_SEPARATOR .
    GEMS_TEST_DIR . '/classes' . PATH_SEPARATOR .
    GEMS_TEST_DIR . '/library' . PATH_SEPARATOR .
    GEMS_ROOT_DIR . '/classes' . PATH_SEPARATOR .
    GEMS_LIBRARY_DIR . '/classes' . PATH_SEPARATOR .
    get_include_path());

// Set up autoload.
require_once VENDOR_DIR . '/autoload.php';

\Zend_Session::start();
\Zend_Session::$_unitTestEnabled = true;
