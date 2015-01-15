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
 *
 * @version    $Id$
 * @package    Gems
 * @subpackage Log
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

/**
 * @package    Gems
 * @subpackage Log
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */
class Gems_Log extends Zend_Log
{
    /**
     * Static instance
     * @var Gems_Log
     */
    private static $_instance = null;

    /**
     * Returns static instance
     * @return Gems_Log
     */
    public static function getLogger()
    {
        if (empty(self::$_instance)) {
            self::$_instance = new Gems_Log();
        }

        return self::$_instance;
    }

    /**
     * Strips HTML tags from text
     * @param  string $text
     * @return string
     */
    protected function stripHtml($text)
    {
        $text = str_replace('>', ">\n", $text);
        return strip_tags($text);
    }

    /**
     * Helper method to log exception and (optional) request information
     * @param Exception                        $exception
     * @param Zend_Controller_Request_Abstract $request
     */
    public function logError(Exception $exception, Zend_Controller_Request_Abstract $request = null)
    {
        $info = array();

        $info[] = 'Class:     ' . get_class($exception);
        $info[] = 'Message:   ' . $this->stripHtml($exception->getMessage());

        if (($exception instanceof Gems_Exception) && ($text = $exception->getInfo())) {
            $info[] = 'Info:      ' . $this->stripHtml($text);
        }

        if (method_exists($exception, 'getChainedException')) {
            $chained = $exception->getChainedException();

            if ($chained) {
                $info[] = '';
                $info[] = 'Chained class:   ' . get_class($chained);
                $info[] = 'Changed message: ' . $this->stripHtml($chained->getMessage());
                if (($chained instanceof Gems_Exception) && ($text = $chained->getInfo())) {
                    $info[] = 'Changed info:    ' . $this->stripHtml($text);
                }
            }
        }
        $previous = $exception->getPrevious();
        while ($previous) {
            $info[] = '';
            $info[] = 'Prevous class:   ' . get_class($previous);
            $info[] = 'Prevous message: ' . $this->stripHtml($previous->getMessage());
            if (($previous instanceof Gems_Exception) && ($text = $previous->getInfo())) {
                $info[] = 'Previous info:    ' . $this->stripHtml($text);
            }
            $previous = $previous->getPrevious();
        }

        foreach ($info as $line) {
            $this->log($line, Zend_Log::ERR);
        }

        // Now empty as we are going to log potentially sensitive debug data
        // We log this with a Zend_Log::DEBUG level, so filter can strip it if needed
        $info = array();

        if (!empty($request)) {
            $info[] = 'Request Parameters:';
            foreach ($request->getParams() as $key => $value) {
                if (!is_array($value)) {
                    // Make sure a password does not end in the logfile
                    if (false === strpos(strtolower($key), 'password')) {
                        $info[] = $key . ' => ' . $value;
                    } else {
                        $info[] = $key . ' => ' . str_repeat('*', strlen($value));
                    }
                }
            }
        }

        $info[] = 'Stack trace:';
        $info[] = $exception->getTraceAsString();

        $priority = Zend_Log::DEBUG;
        if (($exception instanceof Zend_Db_Exception) ||
                ($exception instanceof Gems_Exception_Coding)
                ){
            $priority = Zend_Log::ERR;
        }

        foreach ($info as $line) {
            $this->log($line, Zend_Log::DEBUG);
        }
    }

    /**
     * Closes all writers.
     */
    public function shutdown()
    {
        foreach ($this->_writers as $writer) {
            $writer->shutdown();
        }
    }
}