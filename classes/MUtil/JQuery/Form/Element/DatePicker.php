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
 * Extension of ZendX DatePicker element that add's locale awareness and input and output date
 * parsing to the original element.
 *
 * @see ZendX_JQuery_Form_Element_DatePicker
 *
 * @package    MUtil
 * @subpackage JQuery
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class MUtil_JQuery_Form_Element_DatePicker extends ZendX_JQuery_Form_Element_DatePicker
{
    protected $_dateFormat;
    protected $_dateValue;
    protected $_storageFormat;

    protected function _applyDateFormat()
    {
        if ($this->_dateValue instanceof Zend_Date) {
            parent::setValue($this->_dateValue->toString($this->getDateFormat()));
        }
        return $this;
    }

    public function getDateFormat()
    {
        return $this->_dateFormat;
    }

    public function getDateValue()
    {
        if ($this->_value && (! $this->_dateValue)) {
            $this->setDateValue($this->_value);
        }
        return $this->_dateValue;
    }

    public function getStorageFormat()
    {
        return $this->_storageFormat;
    }

    public function getValue()
    {
        return parent::getValue();
    }

    /**
     * Validate element value
     *
     * If a translation adapter is registered, any error messages will be
     * translated according to the current locale, using the given error code;
     * if no matching translation is found, the original message will be
     * utilized.
     *
     * Note: The *filtered* value is validated.
     *
     * @param  mixed $value
     * @param  mixed $context
     * @return boolean
     */
    public function isValid($value, $context = null)
    {
        $validators = $this->getValidators();

        if (! $this->getValidator('IsDate')) {
            // Always as first validator
            $isDate = new MUtil_Validate_Date_IsDate();
            array_unshift($validators, $isDate);
            $this->setValidators($validators);
        }

        if ($format = $this->getDateFormat()) {
            // Set the dataFormat if settable
            foreach ($validators as $validator) {
                if (($validator instanceof MUtil_Validate_Date_FormatInterface)
                    || method_exists($validator, 'setDateFormat')) {
                    $validator->setDateFormat($format);
                }
            }
        }

        return parent::isValid($value, $context);
    }

    public function setDateFormat($format)
    {
        $view = $this->getView();
        $this->setJQueryParam('dateFormat', $view->getHelper('DatePicker')->resolveZendLocaleToDatePickerFormat($format));
        $this->_dateFormat = $format;
        $this->_applyDateFormat();

        return $this;
    }

    public function setDateValue($value)
    {
        // MUtil_Echo::r('Input: ' . $value);
        if (null === $value || '' === $value) {
            $this->_dateValue = null;
        } else {
            if ($value instanceof Zend_Date) {
                $this->_dateValue = $value;
            } else {
                if (($format = $this->getDateFormat()) && Zend_Date::isDate($value, $format)) {
                    $this->_dateValue = new Zend_Date($value, $format);

                } elseif (($storageFormat = $this->getStorageFormat()) && Zend_Date::isDate($value, $storageFormat)) {
                    $this->_dateValue = new Zend_Date($value, $storageFormat);

                } elseif ($format || $storageFormat) {
                    //Invalid dateformat, should be handled by validator, just ignore the datevalue
                    //but set the real value so validation runs fine
                    $this->_dateValue = null;
                } else {
                    try {
                        $this->_dateValue = new Zend_Date($value);
                    } catch (Zend_Date_Exception $zde) {
                        $this->_dateValue = null;
                    }
                }
            }
        }
        if ($this->_dateValue instanceof Zend_Date) {
            $this->_applyDateFormat();
        } else {
            parent::setValue($value);
        }
        return $this;
    }

    public function setStorageFormat($format)
    {
        $this->_storageFormat = $format;

        return $this;
    }

    /**
     * Set element value
     *
     * @param  mixed $value
     * @return Zend_Form_Element
     */
    public function setValue($value)
    {
        $this->setDateValue($value);
        return $this;
    }

    public function setView(Zend_View_Interface $view = null)
    {
        /*
          http://jquery-ui.googlecode.com/svn/tags/latest/ui/minified/i18n/jquery-ui-i18n.min.js"

            $(function() {
                $.datepicker.setDefaults($.datepicker.regional['']);
                $("#datepicker").datepicker($.datepicker.regional['fr']);
                $("#locale").change(function() {
                    $('#datepicker').datepicker('option', $.datepicker.regional[$(this).val()]);
                });
            });

         */

        $element = parent::setView($view);

        if (null !== $view) {
            if (false === $view->getPluginLoader('helper')->getPaths('MUtil_JQuery_View_Helper')) {
                $view->addHelperPath('MUtil/JQuery/View/Helper', 'MUtil_JQuery_View_Helper');
            }
        }


        if ($locale = Zend_Registry::get('Zend_Locale')) {
            $language = $locale->getLanguage();
            if ($language && $language != 'en') {
                //TODO : work out correct link / setup: now defaults to last language
                // $view->jquery()->addJavascriptFile(ZendX_JQuery::CDN_BASE_GOOGLE . ZendX_JQuery::CDN_SUBFOLDER_JQUERYUI . ZendX_JQuery::DEFAULT_UI_VERSION . '/i18n/jquery-ui-i18n.min.js');

                // $ljs = "$('#" . $this->getId() . "').datepicker('option', $.datepicker.regional['" . $language . "']);";
                // $view->JQuery()->addOnLoad($ljs);

                $http = MUtil_Https::on() ? ZendX_JQuery::CDN_BASE_GOOGLE_SSL : ZendX_JQuery::CDN_BASE_GOOGLE ;
                $version = ZendX_JQuery::DEFAULT_UI_VERSION;
                $version = '1.8.9'; //Overrule want vertaling nederlands wordt anders niet gevonden
                $view->JQuery()->addJavascriptFile($http . ZendX_JQuery::CDN_SUBFOLDER_JQUERYUI . $version . '/i18n/jquery.ui.datepicker-' . $language . '.js');
            }
        }

        return $element;
    }
}
