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
 * @package    MUtil
 * @subpackage JQuery
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 * @package    MUtil
 * @subpackage JQuery
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class MUtil_JQuery_View_Helper_DatePicker extends ZendX_JQuery_View_Helper_DatePicker
{
    /**
     * Create a jQuery UI Widget Date Picker
     *
     * @link   http://docs.jquery.com/UI/Datepicker
     * @link   http://trentrichardson.com/examples/timepicker
     *
     * @static boolean $sayThisOnlyOnce Output JavaScript only once.
     * @param  string $id
     * @param  string $value
     * @param  array  $params jQuery Widget Parameters
     * @param  array  $attribs HTML Element Attributes
     * @return string
     */
    public function datePicker($id, $value = null, array $params = array(), array $attribs = array())
    {
        static $sayThisOnlyOnce = true;

        $attribs = $this->_prepareAttributes($id, $value, $attribs);
        $picker  = 'datepicker';

        $formatDate = isset($params['dateFormat']) && $params['dateFormat'];
        $formatTime = isset($params['timeFormat']) && $params['timeFormat'];

        // MUtil_Echo::track($params['dateFormat'], $params['timeFormat']);
        if ((!isset($params['dateFormat'])) && (!isset($params['timeFormat'])) && Zend_Registry::isRegistered('Zend_Locale')) {
            $params['dateFormat'] = self::resolveZendLocaleToDatePickerFormat();
        }
        if ($formatDate) {
            if ($formatTime) {
                $picker  = 'datetimepicker';
            }
        } elseif ($formatTime) {
            $picker  = 'timepicker';
        }
        if (isset($params['timeJsUrl'])) {
            $baseurl = $params['timeJsUrl'];
            unset($params['timeJsUrl']);
        } else {
            $baseurl = false;
        }

        $params = ZendX_JQuery::encodeJson($params);

        $js = sprintf('%s("#%s").%s(%s);',
                ZendX_JQuery_View_Helper_JQuery::getJQueryHandler(),
                $attribs['id'],
                $picker,
                $params
        );

        if ($formatTime && $sayThisOnlyOnce) {

            $files[] = 'jquery-ui-timepicker-addon.js';
            if ($locale = Zend_Registry::get('Zend_Locale')) {
                $language = $locale->getLanguage();
                // We have a language, but only when not english
                if ($language && $language != 'en') {
                    $files[] = sprintf('i18n/jquery-ui-timepicker-addon-%s.js', $language);
                }
            }

            if ($baseurl) {
                foreach ($files as $file) {
                    $this->jquery->addJavascriptFile($baseurl . '/' . $file);
                }
            } else {
                foreach ($files as $file) {
                    if (file_exists(__DIR__ . '/js/' . $file)) {
                        $js = "\n// File: $file\n\n" . file_get_contents(__DIR__ . '/js/' . $file) . "\n\n" . $js;
                    }
                }
            }

            $sayThisOnlyOnce = false;
        }

        $this->jquery->addOnLoad($js);

        $onload = $this->onLoadJs(
                $id,
                $picker,
                isset($attribs['disabled']),
                isset($params['dateFormat']) && $params['dateFormat']);

        $onload->render($this->view);

        return $this->view->formText($id, $value, $attribs);
    }

    /**
     * Create a JavaScript onload element
     *
     * @param string $id
     * @param string $picker
     * @param boolean $disabled
     * @param boolean $dateFormat
     * @return \MUtil_Html_Code_JavaScript
     */
    public function onLoadJs($id, $picker, $disabled, $dateFormat)
    {
        $onload = new MUtil_Html_Code_JavaScript(array('ELEM_ID' => $id, 'PICKER' => $picker));

        if ($disabled) {
            $onload->addContent(__DIR__ . '/js/datepicker.disabled.js');
        }

        if ($dateFormat) {
            $onload->addContent(__DIR__ . '/js/datepicker.formatdate.js');
        }

        return $onload;
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
     *  - "yyyy-MM-dd hh:mm"  => array("yy-mm-dd", " ", "hh:mm")
     *  - "X yyyy-MM-dd X"    => array("X yy-mm-dd X", false, false)
     *  - "yy \"hi': mm\" MM" => array("y 'hi'': mm' mm", false, false)
     *  - "yyyy-MM-dd 'date: yyyy-MM-dd' HH:mm 'time'': hh:mm' zzzz Q", => array("yy-mm-dd", " 'date: yyyy-MM-dd' ", "HH:mm 'time'': hh:mm' z Q")
     *  - "HH:mm:ss"          => array(false, false, "HH:mm:ss")
     *  - Zend_Date::ISO_8601 => array("ISO_8601", "T", "HH:mm:ssZ")
     *
     * @param string $format Or Zend_Locale_Format::getDateFormat($locale)
     * @return array dateFormat, seperator, timeFormat
     */
    public static function splitZendLocaleToDateTimePickerFormat($format=null)
    {
        if($format == null) {
            $locale = Zend_Registry::get('Zend_Locale');
            if( !($locale instanceof Zend_Locale) ) {
                require_once "ZendX/JQuery/Exception.php";
                throw new ZendX_JQuery_Exception("Cannot resolve Zend Locale format by default, no application wide locale is set.");
            }
            /**
             * @see Zend_Locale_Format
             */
            require_once "Zend/Locale/Format.php";
            $format = Zend_Locale_Format::getDateFormat($locale);
        }

        $fullDates = array(
            Zend_Date::ATOM     => array('ATOM',     'T', 'HH:mm:ssZ' ), // No timezone +01:00, use +0100
            Zend_Date::COOKIE   => array('COOKIE',   ' ', 'HH:mm:ss z'),
            Zend_Date::ISO_8601 => array('ISO_8601', 'T', 'HH:mm:ssZ' ),
            Zend_Date::RFC_822  => array('RFC_822',  ' ', 'HH:mm:ss Z'), // No timezone +01:00, use +0100
            Zend_Date::RFC_850  => array('RFC_850',  ' ', 'HH:mm:ss z'),
            Zend_Date::RFC_1036 => array('RFC_1036', ' ', 'HH:mm:ss Z'),
            Zend_Date::RFC_1123 => array('RFC_1123', ' ', 'HH:mm:ss z'),
            Zend_Date::RFC_2822 => array('RFC_2822', ' ', 'HH:mm:ss Z'),
            Zend_Date::RFC_3339 => array('yy-mm-dd', 'T', 'HH:mm:ssZ' ), // No timezone +01:00, use +0100
            Zend_Date::RSS      => array('RSS',      ' ', 'HH:mm:ss Z'),
            Zend_Date::W3C      => array('W3C',      'T', 'HH:mm:ssZ' ), // No timezone +01:00, use +0100
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
                    throw new Zend_Form_Element_Exception(sprintf(
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

        // MUtil_Echo::track($preg);
        return array($dateFormat, $separator, $timeFormat);
    }
}