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
define('GEMS_WEB_DIR', realpath(dirname(__FILE__) . '/../'));
define('GEMS_ROOT_DIR', realpath(dirname(__FILE__) . '/../../'));
define('GEMS_LIBRARY_DIR', realpath(dirname(__FILE__) . '/../../library/Gems'));
define('GEMS_PROJECT_NAME_UC', ucfirst(GEMS_PROJECT_NAME));
define('APPLICATION_ENV', 'development');
define('APPLICATION_PATH', GEMS_ROOT_DIR . '/application');

/**
 * Setup include path
 */
set_include_path(
    GEMS_WEB_DIR . '/classes' . PATH_SEPARATOR .    //Test folder
    GEMS_WEB_DIR . '/library' . PATH_SEPARATOR .    //Test folder
    GEMS_LIBRARY_DIR . '/classes' . PATH_SEPARATOR .
    GEMS_ROOT_DIR . '/application/classes' . PATH_SEPARATOR .
    get_include_path());

// Set up autoload.
require_once "Zend/Loader/Autoloader.php";
$autoloader = \Zend_Loader_Autoloader::getInstance();
$autoloader->registerNamespace('MUtil_');
$autoloader->registerNamespace('Gems_');
$autoloader->registerNameSpace(GEMS_PROJECT_NAME_UC . '_');
$autoloader->registerNameSpace('ZFDebug_');

\Zend_Session::$_unitTestEnabled = true;
