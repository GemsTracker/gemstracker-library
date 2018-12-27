<?php

/**
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
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
class Gems_Default_ErrorAction extends \MUtil_Controller_Action
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
            case \Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
            case \Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
                // 404 error -- controller or action not found
                $responseCode = 404;
                $message      = 'Page not found';
                break;

            case \Zend_Controller_Plugin_ErrorHandler::EXCEPTION_OTHER:

                if ($exception instanceof \Gems_Exception) {
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

        \Gems_Log::getLogger()->logError($errors->exception, $errors->request);

        if (\MUtil_Console::isConsole()) {
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
