<?php

/**
 * Unit test bootstrap
 *
 * @author Michiel Rook <michiel@touchdownconsulting.nl>
 * @version $Id: bootstrap.php 361 2011-07-28 14:58:34Z michiel $
 * @package Gems
 */

date_default_timezone_set('Europe/Amsterdam');

/**
 * Setup environment
 */
define('GEMS_TEST_DIR', realpath(dirname(__FILE__)));
define('GEMS_ROOT_DIR', realpath(dirname(__FILE__) . '/../'));
define('GEMS_LIBRARY_DIR', realpath(dirname(__FILE__) . '/../'));
define('GEMS_WEB_DIR', dirname(__FILE__));
define('APPLICATION_ENV', 'testing');
define('GEMS_PROJECT_NAME', 'Gems');
define('GEMS_PROJECT_NAME_UC', 'Gems');
define('APPLICATION_PATH', GEMS_LIBRARY_DIR);

defined('VENDOR_DIR') || define('VENDOR_DIR', realpath(GEMS_TEST_DIR . '/../vendor/'));
defined('MUTIL_LIBRARY_DIR') || define('MUTIL_LIBRARY_DIR', realpath(VENDOR_DIR . '/magnafacta'));

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
    GEMS_ROOT_DIR . '/classes');

// Set up autoload.
if (file_exists(dirname(__FILE__) . '/../vendor/autoload.php')) {
    require_once dirname(__FILE__) . '/../vendor/autoload.php';
} else {
    // Try to set the correct include path (if needed)
    $paths = array(
        'magnafacta/mutil/src',
        'magnafacta/mutil/tests',
        'zendframework/zendframework1/library',
        'zendframework/zf1-extras/library',
    );
    $start = realpath(dirname(__FILE__) . '/../../../') . '/';
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

// print_r(explode(PATH_SEPARATOR, get_include_path()));
