<?php

/**
 * Unit test bootstrap
 *
 * @author Michiel Rook <michiel@touchdownconsulting.nl>
 * @version $Id: bootstrap.php 361 2011-07-28 14:58:34Z michiel $
 * @package Gems
 */

defined('GEMS_TIMEZONE') || define('GEMS_TIMEZONE', 'Europe/Amsterdam');
date_default_timezone_set(GEMS_TIMEZONE);

/**
 * Setup environment
 */
define('APPLICATION_ENV', 'testing');
define('GEMS_PROJECT_NAME', 'Gems');
define('GEMS_PROJECT_NAME_UC',  'Gems');

define('GEMS_TEST_DIR', __DIR__);
define('GEMS_ROOT_DIR', dirname(GEMS_TEST_DIR));
define('GEMS_WEB_DIR', GEMS_TEST_DIR);

defined('APPLICATION_PATH') || define('APPLICATION_PATH', GEMS_ROOT_DIR);
if (! defined('VENDOR_DIR')) {
    $path = realpath(GEMS_ROOT_DIR . '/vendor/');
    if (is_dir($path)) {
        define('VENDOR_DIR', $path);
    } else {
        define('VENDOR_DIR', dirname(dirname(GEMS_ROOT_DIR)));
    }
}
defined('GEMS_LIBRARY_DIR') || define('GEMS_LIBRARY_DIR', GEMS_ROOT_DIR);
defined('MUTIL_LIBRARY_DIR') || define('MUTIL_LIBRARY_DIR', realpath(VENDOR_DIR . '/magnafacta/mutil/src'));
defined('APPLICATION_PATH') || define('APPLICATION_PATH', GEMS_LIBRARY_DIR);

// Make sure session save path is writable for current user (needed for Jenkins)
if (!is_writable( session_save_path())) {
     session_save_path(GEMS_TEST_DIR . '/tmp');
}

// Set up autoload for travis.
if (file_exists(dirname(__FILE__) . '/../vendor/autoload.php')) {
    /**
     * Setup include path
     */
    set_include_path(
        GEMS_TEST_DIR . '/classes' . PATH_SEPARATOR .
        GEMS_TEST_DIR . '/library' . PATH_SEPARATOR .
        GEMS_ROOT_DIR . '/classes'
        );

    require_once dirname(__FILE__) . '/../vendor/autoload.php';
} else {
    /**
     * Setup include path
     */
    set_include_path(
        GEMS_TEST_DIR . '/classes' . PATH_SEPARATOR .
        GEMS_TEST_DIR . '/library' . PATH_SEPARATOR .
        GEMS_LIBRARY_DIR . '/classes'
        );

    // Try to set the correct include path (if needed)
    $paths = array(
        'magnafacta/mutil/src',
        'magnafacta/mutil/tests',
        'zendframework/zendframework1/library',
        'zendframework/zf1-extras/library',
    );
    $start = VENDOR_DIR . DIRECTORY_SEPARATOR;
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
