<?php

/**

/**
 * This files contains general project code that set constants and initializes
 * PHP for use with Zend / Gemstracker. Next the autoloader and Zend_Application
 * are created and the bootstrap is started.
 *
 * @author Matijs de Jong <mjong@magnafacta.nl>
 * @since 1.0
 * @version $Id: pre_bootstrap.php 2659 2015-07-30 16:10:15Z jvangestel $
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
$application = new Zend_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/configs/application.ini'
);

// MUtil_Model::$verbose = true;

$application->bootstrap()
            ->run();