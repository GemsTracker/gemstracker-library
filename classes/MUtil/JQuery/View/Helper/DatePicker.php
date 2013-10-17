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
     * @param  string $id
     * @param  string $value
     * @param  array  $params jQuery Widget Parameters
     * @param  array  $attribs HTML Element Attributes
     * @return string
     */
    public function datePicker($id, $value = null, array $params = array(), array $attribs = array())
    {
        $attribs = $this->_prepareAttributes($id, $value, $attribs);
        $picker  = 'datepicker';

        $formatDate = isset($params['dateFormat']) && $params['dateFormat'];
        $formatTime = false && isset($params['timeFormat']) && $params['timeFormat'];

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
        // unset($params['timeFormat']);

        // TODO: Allow translation of DatePicker Text Values to get this action from client to server
        $params = ZendX_JQuery::encodeJson($params);

        $js = sprintf('%s("#%s").%s(%s);',
                ZendX_JQuery_View_Helper_JQuery::getJQueryHandler(),
                $attribs['id'],
                $picker,
                $params
        );

        if ($formatTime) {
            $js = file_get_contents(__DIR__ . '/js/jquery-ui-timepicker-addon.js') . "\n\n" . $js;
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
     * A Check for Zend_Locale existance has already been done in {@link datePicker()}
     * this function only resolves the default format from Zend Locale to
     * a jQuery Time Picker readable format.
     *
     * This function can be potentially buggy because of its easy nature and is therefore
     * stripped from the core functionality to be easily overriden.
     *
     * @param string $format
     * @return string
     */
    public static function resolveZendLocaleToTimePickerFormat($format=null)
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

        $dateFormat = array(
            'EEEEE' => '', 'EEEE' => '', 'EEE' => '', 'EE' => '', 'E' => '',
            'MMMM' => '', 'MMM' => '', 'MM' => '', 'M' => '', 'D' => '', 'd' => '',
            'YYYYY' => '', 'YYYY' => '', 'YYY' => '', 'YY' => '', 'Y' => '',
            'yyyyy' => '', 'yyyy' => '', 'yyy' => '', 'yy' => '', 'y' => '',
            'G' => '', 'e' => '', 'a' => 'tt', 'hh' => 'hh', 'h' => 'h', 'HH' => 'HH',
            'H' => 'H', 'mm' => 'mm', 'm' => 'm', 'ss' => 'ss', 's' => 's', 'S' => 'l',
            'zzzz' => 'z', 'zzz' => 'z', 'zz' => 'z', 'z' => 'z', 'ZZZZ' => 'Z',
            'ZZZ' => 'Z', 'ZZ' => 'Z', 'Z' => 'Z', 'A' => '',
        );

        $replacedAny = false;
        $newFormat = "";
        $isText = false;
        $i = 0;
        while($i < strlen($format)) {
            $chr = $format[$i];
            if($chr == '"' || $chr == "'") {
                $isText = !$isText;
            }
            $replaced = false;
            if($isText == false) {
                foreach($dateFormat AS $zl => $jql) {
                    if(substr($format, $i, strlen($zl)) == $zl) {
                        $chr = $jql;
                        $i += strlen($zl);
                        $replaced = true;
                        $replacedAny = $replacedAny && strlen($jql);
                    }
                }
            }
            if($replaced == false) {
                $i++;
            }
            $newFormat .= $chr;
        }

        if ($replacedAny) {
            return $newFormat;
        }
    }
}