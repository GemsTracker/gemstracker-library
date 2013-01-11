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

// PHP ENCODING SETUP
defined('APPLICATION_ENCODING') || define('APPLICATION_ENCODING', 'UTF-8');

mb_internal_encoding(APPLICATION_ENCODING);

// ZEND FRAMEWORK STARTS HERE

/**
 *  Define path to application directory
 */
defined('APPLICATION_PATH') || define('APPLICATION_PATH', GEMS_ROOT_DIR . '/application');

/**
 * Set path to Zend Framework
 * then to project directory
 * then to Gems application directory
 */
set_include_path(
    APPLICATION_PATH . '/classes' . PATH_SEPARATOR .
    GEMS_LIBRARY_DIR . '/classes' . PATH_SEPARATOR .
    get_include_path()
    );

// Set up autoload for MUtil
require_once 'Zend/Loader/Autoloader.php';
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->registerNamespace('MUtil_');

// Start using cached Loader
// $cachedloader = MUtil_Loader_CachedLoader::getInstance(GEMS_ROOT_DIR . '/var/cache');
// $autoloader->setDefaultAutoloader(array($cachedloader, 'autoload'));

// Create application, bootstrap, and run
$application = new Zend_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/configs/application.ini'
);

// MUtil_Model::$verbose = true;

$application->bootstrap()
            ->run();
