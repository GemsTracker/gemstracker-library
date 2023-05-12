<?php

namespace Gems\Log;

class LogHelper
{
     public static function getMessageFromException(\Exception $exception, \Zend_Controller_Request_Abstract $request = null): string
     {
         $info = [];

         $info[] = 'Class:     ' . get_class($exception);
         $info[] = 'Message:   ' . static::stripHtml($exception->getMessage());

         if (($exception instanceof \Gems\Exception) && ($text = $exception->getInfo())) {
             $info[] = 'Info:      ' . static::stripHtml($text);
         }

         if (method_exists($exception, 'getChainedException')) {
             $chained = $exception->getChainedException();

             if ($chained) {
                 $info[] = '';
                 $info[] = 'Chained class:   ' . get_class($chained);
                 $info[] = 'Changed message: ' . static::stripHtml($chained->getMessage());
                 if (($chained instanceof \Gems\Exception) && ($text = $chained->getInfo())) {
                     $info[] = 'Changed info:    ' . static::stripHtml($text);
                 }
             }
         }
         $previous = $exception->getPrevious();
         while ($previous) {
             $info[] = '';
             $info[] = 'Previous class:   ' . get_class($previous);
             $info[] = 'Previous message: ' . static::stripHtml($previous->getMessage());
             if (($previous instanceof \Gems\Exception) && ($text = $previous->getInfo())) {
                 $info[] = 'Previous info:    ' . static::stripHtml($text);
             }
             $previous = $previous->getPrevious();
         }

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

         return join("\n", $info);
     }

    /**
     * Strips HTML tags from text
     * @param  string $text
     * @return string
     */
    public static function stripHtml(string $text): string
    {
        $text = str_replace('>', ">\n", $text);
        return strip_tags($text);
    }
}
