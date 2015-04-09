<?php

/**
 * Copyright (c) 2014, Erasmus MC
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
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    MUtil
 * @subpackage Date
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id: Format.php 1640 2013-11-19 16:21:36Z matijsdejongs $
 */

/**
 * A static helper class to do stuff with date/time formats
 *
 * @package    MUtil
 * @subpackage Date
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.4 27-feb-2014 13:16:26
 */
class MUtil_Date_Format
{
    /**
     * Return the time part of a format
     *
     * @param string $format
     * @return string
     */
    public static function getTimeFormat($format = null)
    {
        list($dateFormat, $separator, $timeFormat) = self::splitDateTimeFormat($format);

        return $timeFormat;
    }

    /**
     * This function splits a date time format into a date, separator and time part; the last two
     * only when there are time parts in the format.
     *
     * The results are formats readable by the jQuery Date/Time Picker.
     *
     * No date formats are allowed after the start of the time parts. (A future extension
     * might be to allow either option, but datetimepicker does not understand that.)
     *
     * Some examples:
     *  - "yyyy-MM-dd HH:mm"  => array("yy-mm-dd", " ", "hh:mm")
     *  - "X yyyy-MM-dd X"    => array("X yy-mm-dd X", false, false)
     *  - "yy \"hi': mm\" MM" => array("y 'hi'': mm' mm", false, false)
     *  - "yyyy-MM-dd 'date: yyyy-MM-dd' HH:mm 'time'': hh:mm' HH:mm Q", => array("yy-mm-dd", " 'date: yyyy-MM-dd' ", "HH:mm 'time'': HH:mm' z Q")
     *  - "HH:mm:ss"          => array(false, false, "HH:mm:ss")
     *  - \Zend_Date::ISO_8601 => array("ISO_8601", "T", "HH:mm:ssZ")
     *
     * @param string $format Or \Zend_Locale_Format::getDateFormat($locale)
     * @return array dateFormat, seperator, timeFormat
     */
    public static function splitDateTimeFormat($format=null)
    {
        if($format == null) {
            $locale = \Zend_Registry::get('Zend_Locale');
            if(! ($locale instanceof \Zend_Locale) ) {
                throw new \ZendX_JQuery_Exception("Cannot resolve Zend Locale format by default, no application wide locale is set.");
            }
            /**
             * @see \Zend_Locale_Format
             */
            $format = \Zend_Locale_Format::getDateFormat($locale);
        }

        $fullDates = array(
            \Zend_Date::ATOM     => array('ATOM',     'T', 'HH:mm:ssZ' ), // No timezone +01:00, use +0100
            \Zend_Date::COOKIE   => array('COOKIE',   ' ', 'HH:mm:ss z'),
            \Zend_Date::ISO_8601 => array('ISO_8601', 'T', 'HH:mm:ssZ' ),
            \Zend_Date::RFC_822  => array('RFC_822',  ' ', 'HH:mm:ss Z'), // No timezone +01:00, use +0100
            \Zend_Date::RFC_850  => array('RFC_850',  ' ', 'HH:mm:ss z'),
            \Zend_Date::RFC_1036 => array('RFC_1036', ' ', 'HH:mm:ss Z'),
            \Zend_Date::RFC_1123 => array('RFC_1123', ' ', 'HH:mm:ss z'),
            \Zend_Date::RFC_2822 => array('RFC_2822', ' ', 'HH:mm:ss Z'),
            \Zend_Date::RFC_3339 => array('yy-mm-dd', 'T', 'HH:mm:ssZ' ), // No timezone +01:00, use +0100
            \Zend_Date::RSS      => array('RSS',      ' ', 'HH:mm:ss Z'),
            \Zend_Date::W3C      => array('W3C',      'T', 'HH:mm:ssZ' ), // No timezone +01:00, use +0100
        );

        if (isset($fullDates[$format])) {
            return $fullDates[$format];
        }

        $dateFormats = array(
            'EEEEE' => 'D', 'EEEE' => 'DD', 'EEE' => 'D', 'EE' => 'D', 'E' => 'D',
            'YYYYY' => 'yy', 'YYYY' => 'yy', 'YYY' => 'yy', 'YY' => 'y', 'Y' => 'yy',
            'yyyyy' => 'yy', 'yyyy' => 'yy', 'yyy' => 'yy', 'yy' => 'y', 'y' => 'yy',
            'MMMM' => 'MM', 'MMM' => 'M', 'MM' => 'mm', 'M' => 'm',
            'dd' => 'dd', 'd' => 'd', 'DDD' => 'oo', 'DD' => 'o', 'D' => 'o',
            'G' => '', 'e' => '', 'w' => '',
        );
        $timeFormats = array(
            'a' => 'tt', 'hh' => 'hh', 'h' => 'h', 'HH' => 'HH',
            'H' => 'H', 'mm' => 'mm', 'm' => 'm', 'ss' => 'ss', 's' => 's', 'S' => 'l',
            'zzzz' => 'z', 'zzz' => 'z', 'zz' => 'z', 'z' => 'z', 'ZZZZ' => 'Z',
            'ZZZ' => 'Z', 'ZZ' => 'Z', 'Z' => 'Z', 'A' => '',
        );

        $pregs[] = '"[^"]*"'; // Literal text
        $pregs[] = "'[^']*'"; // Literal text
        $pregs   = array_merge($pregs, array_keys($dateFormats), array_keys($timeFormats)); // Add key words
        $preg    = sprintf('/(%s)/', implode('|', $pregs));

        $parts = preg_split($preg, $format, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

        $cache      = '';
        $dateFormat = false;
        $separator  = false;
        $timeFormat = false;

        foreach ($parts as $part) {
            if (isset($dateFormats[$part])) {
                if (false !== $timeFormat) {
                    throw new \Zend_Form_Element_Exception(sprintf(
                            'Date format specifier %s not allowed after time specifier in %s in format mask %s.',
                            $part,
                            $timeFormat,
                            $format
                            ));
                }
                $dateFormat .= $cache . $dateFormats[$part];
                $cache      = '';

            } elseif (isset($timeFormats[$part])) {
                // Switching to time format mode
                if (false === $timeFormat) {
                    if ($dateFormat) {
                        $separator  = $cache;
                        $timeFormat = $timeFormats[$part];
                    } else {
                        $timeFormat = $cache . $timeFormats[$part];
                    }
                } else {
                    $timeFormat .= $cache . $timeFormats[$part];
                }
                $cache = '';

            } elseif ('"' === $part[0]) {
                // Replace double quotes with single quotes, single quotes in string with two single quotes
                $cache .= strtr($part, array('"' => "'", "'" => "''"));

            } else {
                $cache .= $part;
            }
        }
        if ($cache) {
            if (false === $timeFormat) {
                $dateFormat .= $cache;
            } else {
                $timeFormat .= $cache;
            }
        }

        // \MUtil_Echo::track($preg);
        return array($dateFormat, $separator, $timeFormat);
    }
}
