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
 *
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
define('GEMS_WEB_DIR', realpath(dirname(__FILE__) . '/../new_project/htdocs'));
define('GEMS_ROOT_DIR', realpath(dirname(__FILE__) . '/../new_project'));
define('GEMS_LIBRARY_DIR', realpath(dirname(__FILE__) . '/../library'));
define('GEMS_PROJECT_NAME', 'newProject');
define('GEMS_PROJECT_NAME_UC', ucfirst(GEMS_PROJECT_NAME));
define('APPLICATION_ENV', 'development');
define('APPLICATION_PATH', GEMS_ROOT_DIR . '/application');

// Make sure session save path is writable for current user (needed for Jenkins)
if (!is_writable( session_save_path())) {
     session_save_path(GEMS_ROOT_DIR . '/var/tmp');
}

/**
 * Setup include path
 */
$path = realpath(dirname(__FILE__) . '/../');

set_include_path(
    GEMS_TEST_DIR . '/classes' . PATH_SEPARATOR .
    GEMS_TEST_DIR . '/library' . PATH_SEPARATOR .
    $path . '/library/classes' . PATH_SEPARATOR .
    GEMS_ROOT_DIR . '/application/classes' . PATH_SEPARATOR .
    get_include_path());

// Set up autoload.
require_once "Zend/Loader/Autoloader.php";
$autoloader = Zend_Loader_Autoloader::getInstance();
$autoloader->registerNamespace('MUtil_');
$autoloader->registerNamespace('Gems_');
$autoloader->registerNameSpace('NewProject_');
$autoloader->registerNameSpace('ZFDebug_');

Zend_Session::start();
