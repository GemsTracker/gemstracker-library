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
 * ZFDebug Zend Additions
 *
 * @category   ZFDebug
 * @filesource
 * @package    ZFDebug_Controller
 * @subpackage Plugins
 * @copyright  Copyright (c) 2008-2009 ZF Debug Bar Team (http://code.google.com/p/zfdebug)
 * @license    http://code.google.com/p/zfdebug/wiki/License     New BSD License
 * @version    $Id$
 */

/**
 * @category   ZFDebug
 * @filesource
 * @package    ZFDebug_Controller
 * @subpackage Plugins
 * @copyright  Copyright (c) 2008-2009 ZF Debug Bar Team (http://code.google.com/p/zfdebug)
 * @license    http://code.google.com/p/zfdebug/wiki/License     New BSD License
 */
class ZFDebug_Controller_Plugin_Debug_Plugin_Exception implements ZFDebug_Controller_Plugin_Debug_Plugin_Interface
{
    /**
     * Contains plugin identifier name
     *
     * @var string
     */
    protected $_identifier = 'exception';

    /**
     * Contains any errors
     *
     * @var param array
     */
    static $errors = array();

    /**
     * Gets identifier for this plugin
     *
     * @return string
     */
    public function getIdentifier ()
    {
        return $this->_identifier;
    }

    /**
     * Creates Error Plugin ans sets the Error Handler
     *
     * @return void
     */
    public function __construct ()
    {
        set_error_handler(array($this , 'errorHandler'));
    }

    /**
     * Gets menu tab for the Debugbar
     *
     * @return string
     */
    public function getTab ()
    {
        $response = Zend_Controller_Front::getInstance()->getResponse();
        $errorCount = count(self::$errors);
        if (! $response->isException() && ! $errorCount)
            return '';
        $error = '';
        $exception = '';
        if ($errorCount)
            $error = ($errorCount == 1 ? '1 Error' : $errorCount . ' Errors');
        $count = count($response->getException());
        //if ($this->_options['show_exceptions'] && $count)
        if ($count)
            $exception = ($count == 1) ? '1 Exception' : $count . ' Exceptions';
        $text = $exception . ($exception == '' || $error == '' ? '' : ' - ') . $error;
        return $text;
    }

    /**
     * Gets content panel for the Debugbar
     *
     * @return string
     */
    public function getPanel ()
    {
        $response = Zend_Controller_Front::getInstance()->getResponse();
        $errorCount = count(self::$errors);
        if (! $response->isException() && ! $errorCount)
            return '';
        $html = '';

        foreach ($response->getException() as $e) {
            $html .= '<h4>' . get_class($e) . ': ' . $e->getMessage() . '</h4><p>thrown in ' . $e->getFile() . ' on line ' . $e->getLine() . '</p>';
            $html .= '<h4>Call Stack</h4><ol>';
            foreach ($e->getTrace() as $t) {
                $func = $t['function'] . '()';
                if (isset($t['class']))
                    $func = $t['class'] . $t['type'] . $func;
                if (! isset($t['file']))
                    $t['file'] = 'unknown';
                if (! isset($t['line']))
                    $t['line'] = 'n/a';
                $html .= '<li>' . $func . '<br>in ' . str_replace($_SERVER['DOCUMENT_ROOT'], '', $t['file']) . ' on line ' . $t['line'] . '</li>';
            }
            $html .= '</ol>';
        }

        if ($errorCount) {
            $html .= '<h4>Errors</h4><ol>';
            foreach (self::$errors as $error) {
                $html .= '<li>' . sprintf("%s: %s in %s on line %d", $error['type'], $error['message'], str_replace($_SERVER['DOCUMENT_ROOT'], '', $error['file']), $error['line']) . '</li>';
            }
            $html .= '</ol>';
        }
        return $html;
    }

    /**
     * Debug Bar php error handler
     *
     * @param string $level
     * @param string $message
     * @param string $file
     * @param string $line
     * @return bool
     */
    public static function errorHandler ($level, $message, $file, $line)
    {
        if (! ($level & error_reporting()))
            return false;
        switch ($level) {
            case E_NOTICE:
            case E_USER_NOTICE:
                $type = 'Notice';
                break;
            case E_WARNING:
            case E_USER_WARNING:
                $type = 'Warning';
                break;
            case E_ERROR:
            case E_USER_ERROR:
                $type = 'Fatal Error';
                break;
            default:
                $type = 'Unknown, ' . $level;
                break;
        }
        self::$errors[] = array('type' => $type , 'message' => $message , 'file' => $file , 'line' => $line);
        if (ini_get('log_errors'))
            error_log(sprintf("%s: %s in %s on line %d", $type, $message, $file, $line));
        return true;
    }
}