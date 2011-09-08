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

class Gems_Util_Localized extends Gems_Registry_TargetAbstract
{
    /**
     *
     * @var Zend_Locale
     */
    protected $locale;

    /**
     *
     * @var ArrayObject
     */
    protected $project;

    public function getCountries()
    {
        static $data;

        if (! $data) {
            $data = Zend_Locale::getTranslationList('territory', $this->locale, 2);

            asort($data, SORT_LOCALE_STRING);
        }

        return $data;
    }

    /**
     *
     * @return array Containing all the languages used in this project
     */
    public function getLanguages()
    {
        if (isset($this->project->locales)) {
            $locales = $this->project->locales;
        } elseif (isset($this->project->locale['default'])) {
            $locales = array($this->project->locale['default']);
        } else {
            $locales = array('en');
        }

        foreach ($locales as $locale) {
            $languages[$locale] = Zend_Locale::getTranslation($locale, 'Language', $this->locale);
        }

        asort($languages);

        return $languages;
    }

    public function getMonthName($month)
    {
        static $data;

        if (! $data) {
            $data = Zend_Locale::getTranslationList('month', $this->locale);
        }

        $month = intval($month); // Sometimes month comes through as '02'
        if (isset($data[$month])) {
            return $data[$month];
        }
    }

    /* 
    public function formatDateTime($dateTimeValue, $showRecentTime = false)
    {
        if ($dateTimeValue) {
            $locale = $this->locale;

            $dateTime = strtotime($dateTimeValue);
            $days = floor($dateTime / 86400) - floor(time() / 86400) + ($showRecentTime ? 0 : 1); // 86400 = 24*60*60

            if ($descr = Zend_Locale_Data::getContent($locale, 'relative', $days)) {
                $recentTime = $showRecentTime ? ' ' . date('H:i', $dateTime) : '';
                return $descr . $recentTime;
            }

            $daysDescr = '';
            if (($days > -14) && ($days < 14)) {
                if ($dayFormat = Zend_Locale_Data::getContent($locale, 'unit', array('day', (abs($days) == 1) ? 'one' : 'other'))) {
                    $daysDescr = ' ' . str_replace('{0}', $days, $dayFormat);
                }
            }

            $date = new Zend_Date($dateTimeValue);
            return $date->toString($locale);
            // return date('d-m-Y', $dateTime) . $daysDescr;
        }

        return null;
    } // */

    public function formatNumber($value, $precision = 2)
    {
        if (null !== $value) {
            return Zend_Locale_Format::toNumber($value, array('precision' => $precision, 'locale' => $this->locale));
        }
    }
}