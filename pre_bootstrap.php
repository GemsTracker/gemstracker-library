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
 */

/**
 * This files contains general project code that loads the
 * Zend_Application - and does whatever else has to be done.
 *
 * @author Matijs de Jong <mjong@magnafacta.nl>
 * @since 1.0
 * @version 1.1
 * @package Gems
 * @subpackage Project
 */

// GENERAL PHP SETUP

// Needed for strict on >= PHP 5.1.2
if (version_compare(phpversion(), '5.1.2') > 0) {
    date_default_timezone_set('Europe/Amsterdam');
}

mb_internal_encoding('UTF-8');

// ZEND FRAMEWORK STARTS HERE

/**
 *  Define path to application directory
 */
defined('APPLICATION_PATH') || define('APPLICATION_PATH', GEMS_ROOT_DIR . '/application');

/**
 * Compatibility, remove in 1.6
 */
define('GEMS_PROJECT_PATH', APPLICATION_PATH);

/**
 * Set path to Zend Framework
 * then to project directory
 * then to Gems application directory
 */
set_include_path(
    GEMS_LIBRARY_DIR . '/classes' . PATH_SEPARATOR .
    APPLICATION_PATH . '/classes' . PATH_SEPARATOR .
    get_include_path()
    //. PATH_SEPARATOR . GEMS_ROOT_DIR . '/library'     //Shouldn't be needed, uncomment when neccessary
    );

$GEMS_DIRS = array(
    GEMS_PROJECT_NAME_UC => APPLICATION_PATH . '/classes',
    'Gems' =>               GEMS_LIBRARY_DIR . '/classes'
);

// Make sure Lazy is loaded
// defined('MUTIL_LAZY_FUNCTIONS') || define('MUTIL_LAZY_FUNCTIONS', 1);
require_once 'MUtil/Lazy.php';

// Zend_Application: loads the autoloader
require_once 'Zend/Application.php';

// Create application, bootstrap, and run
$application = new Zend_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/configs/application.ini'
);

// Set up autoload.
// require_once "Zend/Loader/Autoloader.php";
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->registerNamespace('MUtil_');
$autoloader->registerNamespace('Gems_');
$autoloader->registerNamespace(GEMS_PROJECT_NAME_UC . '_');

// Zend_Date::setOptions(array('format_type' => 'php'));

// MUtil_Model::$verbose = true;

$application->bootstrap()
            ->run();
