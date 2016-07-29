<?php

/**
 *
 * @package    Gems
 * @subpackage Log
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * @package    Gems
 * @subpackage Log
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */
class Gems_Log extends \Zend_Log
{
    /**
     * Static instance
     * @var \Gems_Log
     */
    private static $_instance = null;

    /**
     * Returns static instance
     * @return \Gems_Log
     */
    public static function getLogger()
    {
        if (empty(self::$_instance)) {
            self::$_instance = new \Gems_Log();
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
     * @param \Exception                        $exception
     * @param \Zend_Controller_Request_Abstract $request
     */
    public function logError(\Exception $exception, \Zend_Controller_Request_Abstract $request = null)
    {
        $info = array();

        $info[] = 'Class:     ' . get_class($exception);
        $info[] = 'Message:   ' . $this->stripHtml($exception->getMessage());

        if (($exception instanceof \Gems_Exception) && ($text = $exception->getInfo())) {
            $info[] = 'Info:      ' . $this->stripHtml($text);
        }

        if (method_exists($exception, 'getChainedException')) {
            $chained = $exception->getChainedException();

            if ($chained) {
                $info[] = '';
                $info[] = 'Chained class:   ' . get_class($chained);
                $info[] = 'Changed message: ' . $this->stripHtml($chained->getMessage());
                if (($chained instanceof \Gems_Exception) && ($text = $chained->getInfo())) {
                    $info[] = 'Changed info:    ' . $this->stripHtml($text);
                }
            }
        }
        $previous = $exception->getPrevious();
        while ($previous) {
            $info[] = '';
            $info[] = 'Prevous class:   ' . get_class($previous);
            $info[] = 'Prevous message: ' . $this->stripHtml($previous->getMessage());
            if (($previous instanceof \Gems_Exception) && ($text = $previous->getInfo())) {
                $info[] = 'Previous info:    ' . $this->stripHtml($text);
            }
            $previous = $previous->getPrevious();
        }

        foreach ($info as $line) {
            $this->log($line, \Zend_Log::ERR);
        }

        // Now empty as we are going to log potentially sensitive debug data
        // We log this with a \Zend_Log::DEBUG level, so filter can strip it if needed
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

        $priority = \Zend_Log::DEBUG;
        if (($exception instanceof \Zend_Db_Exception) ||
                ($exception instanceof \Gems_Exception_Coding)
                ){
            $priority = \Zend_Log::ERR;
        }

        foreach ($info as $line) {
            $this->log($line, \Zend_Log::DEBUG);
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