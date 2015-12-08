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
 * then to project directory
 * then to Gems application directory
 * then to MUtil application directory
 */
set_include_path(
    APPLICATION_PATH . '/classes' . PATH_SEPARATOR .
    GEMS_LIBRARY_DIR . '/classes' . PATH_SEPARATOR .
    MUTIL_LIBRARY_DIR . PATH_SEPARATOR
    );

require (VENDOR_DIR . '/autoload.php');

// Create application, bootstrap, and run
$application = new Zend_Application(
    APPLICATION_ENV,
    APPLICATION_PATH . '/configs/application.ini'
);

// MUtil_Model::$verbose = true;

$application->bootstrap()
            ->run();
