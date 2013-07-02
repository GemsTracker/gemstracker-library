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
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: ErrorAction.php$
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Default_ErrorAction  extends Zend_Controller_Action
{
    /**
     * Action for displaying an error, CLI as well as HTTP
     */
    public function errorAction()
    {
        $errors       = $this->_getParam('error_handler');
        $exception    = $errors->exception;
        $info         = null;
        $message      = 'Application error';
        $responseCode = 200;

        switch ($errors->type) {
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
                // 404 error -- controller or action not found
                $responseCode = 404;
                $message      = 'Page not found';
                break;

            case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_OTHER:

                if ($exception instanceof Gems_Exception) {
                    $responseCode = $exception->getCode();
                    $message      = $exception->getMessage();
                    $info         = $exception->getInfo();
                    break;
                }
                // Intentional fall through

            default:
                $message = $exception->getMessage();
                break;
        }

        Gems_Log::getLogger()->logError($errors->exception, $errors->request);

        if (MUtil_Console::isConsole()) {
            $this->_helper->viewRenderer->setNoRender(true);

            echo $message . "\n\n";
            if ($info) {
                echo $info . "\n\n";
            }

            $next = $exception->getPrevious();
            while ($next) {
                echo '  ' . $next->getMessage() . "\n";
                $next = $next->getPrevious();
            }

            echo $exception->getTraceAsString();

        } else {
            if ($responseCode) {
                $this->getResponse()->setHttpResponseCode($responseCode);
            }

            $this->view->exception = $exception;
            $this->view->message   = $message;
            $this->view->request   = $errors->request;
            if ($info) {
                $this->view->info = $info;
            }

        }
    }
}
