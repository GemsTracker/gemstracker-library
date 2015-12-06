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
 * @package    MUtil
 * @subpackage Date
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Extends \Zend_Date with extra date math and Utility functions
 *
 * @package    MUtil
 * @subpackage Date
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.0
 */
class MUtil_Date extends \Zend_Date
{
    const DAY_SECONDS = 86400;      // 24 * 60 * 60
    const WEEK_SECONDS = 604800;    // 7 * 24 * 60 * 60

    /**
     * You can add your project specfic dates to this array
     *
     * @var array Zend DateTime Format => PHP DateTime Format
     */
    public static $zendToPhpFormats = array(
        'yyyy-MM-dd HH:mm:ss' => 'Y-m-d H:i:s',
        'yyyy-MM-dd'          => 'Y-m-d',
        'c'                   => \DateTime::ISO8601,
        'dd-MM-yyyy'          => 'd-m-Y',
        'dd-MM-yyyy HH:mm'    => 'd-m-Y H:i',
        'dd-MM-yyyy HH:mm:ss' => 'd-m-Y H:i:s',
        'HH:mm:ss'            => 'H:i:s',
        'HH:mm'               => 'H:i',
    );

    /**
     * Generates the standard date object, could be a unix timestamp, localized date,
     * string, integer, array and so on. Also parts of dates or time are supported
     * Always set the default timezone: http://php.net/date_default_timezone_set
     * For example, in your bootstrap: date_default_timezone_set('America/Los_Angeles');
     * For detailed instructions please look in the docu.
     *
     * @param  string|integer|Zend_Date|array  $date    OPTIONAL Date value or value of date part to set
     *                                                 ,depending on $part. If null the actual time is set
     * @param  string                          $part    OPTIONAL Defines the input format of $date
     * @param  string|Zend_Locale              $locale  OPTIONAL Locale for parsing input
     * @return Zend_Date
     * @throws Zend_Date_Exception
     */
    public function __construct($date = null, $part = null, $locale = null)
    {
        $notset = true;
        if (null == $locale) {
            if (is_string($date) && is_string($part) && isset(self::$zendToPhpFormats[$part])) {
                $phpDate = \DateTime::createFromFormat(self::$zendToPhpFormats[$part], $date);
                if ($phpDate) {
                    $date = $phpDate;
                }

            } elseif ((null == $date) && (null == $part)) {
                $this->setLocale();

                $zone = @date_default_timezone_get();
                $this->setTimezone($zone);
                $this->setUnixTimestamp(time());
                $notset = false;

            }
        }
        if ($date instanceof \DateTime) {
            $this->setLocale();
            $this->setTimezone($date->getTimezone()->getName());
            $this->setUnixTimestamp($date->getTimestamp());
            $notset = false;
        }
        if ($notset) {
            parent::__construct($date, $part, $locale);
        }
    }

    /**
     * The number of days in $date subtracted from $this.
     *
     * Zero when both date/times occur on the same day.
     * POSITIVE when $date is YOUNGER than $this
     * Negative when $date is older than $this
     *
     * @param \Zend_Date $date
     * @param \Zend_Locale $locale optional (not used)
     * @return type
     */
    public function diffDays(\Zend_Date $date = null, $locale = null)
    {
        $val1 = (int) ($this->getUnixTimestamp() / self::DAY_SECONDS);

        if (null === $date) {
            // We must use date objects as unix timestamps do not take
            // account of leap seconds.
            $val2 = (int) (time() / self::DAY_SECONDS);
        } else {
            $val2 = (int) ($date->getUnixTimestamp() / self::DAY_SECONDS);
        }

        return $val1 - $val2;
    }

    /**
     * The number of months in $date subtracted from $this.
     *
     * Zero when both date/times occur in the same month.
     * POSITIVE when $date is YOUNGER than $this
     * Negative when $date is older than $this
     *
     * @param \Zend_Date $date
     * @param \Zend_Locale $locale optional
     * @return type
     */
    public function diffMonths(\Zend_Date $date, $locale = null)
    {
        $val1 = (intval($this->get(\Zend_Date::YEAR, $locale)) * 12) + intval($this->get(\Zend_Date::MONTH, $locale));
        $val2 = (intval($date->get(\Zend_Date::YEAR, $locale)) * 12) + intval($date->get(\Zend_Date::MONTH, $locale));

        return $val1 - $val2;
    }

    /**
     * Returns the difference between this date and the given $date
     *
     * It will always round to the biggest period, so 8 days ago will result in 1 week ago
     * while 13 days ago will result in 2 weeks ago.
     *
     * @param \Zend_Date $date
     * @param \Zend_Translate $translate
     * @return string
     */
    public function diffReadable(\Zend_Date $date, \Zend_Translate $translate)
    {
        $difference = $date->getUnixTimeStamp() - $this->getUnixTimestamp();

        //second, minute, hour, day, week, month, year, decade
        $lengths = array("60", "60", "24", "7", "4.34", "12", "10");

        if ($difference > 0) { // this was in the past
            $ending = $translate->_("%s ago");
        } else { // this was in the future
            $difference = -$difference;
            $ending = $translate->_("%s to go");
        }

        for ($j = 0; $j < 7 && $difference >= $lengths[$j]; $j++) {
            $difference /= $lengths[$j];
        }

        $difference = round($difference);

        switch ($j) {
            case 0:
                $period = $translate->plural('second', 'seconds', $difference);
                break;
            case 1:
                $period = $translate->plural('minute', 'minutes', $difference);
                break;
            case 2:
                $period = $translate->plural('hour', 'hours', $difference);
                break;
            case 3:
                $period = $translate->plural('day', 'days', $difference);
                break;
            case 4:
                $period = $translate->plural('week', 'weeks', $difference);
                break;
            case 5:
                $period = $translate->plural('month', 'months', $difference);
                break;
            case 6:
                $period = $translate->plural('year', 'years', $difference);
                break;
            case 7:
                $period = $translate->plural('decade', 'decades', $difference);
                break;

            default:
                break;
        }
        $time = "$difference $period";
        $text = sprintf($ending, $time);

        return $text;
    }

    /**
     * The number of seconds in $date subtracted from $this.
     *
     * Zero when both date/times occur on the same second.
     * POSITIVE when $date is YOUNGER than $this
     * Negative when $date is older than $this
     *
     * @param \Zend_Date $date Date or now
     * @param \Zend_Locale $locale optional (not used)
     * @return type
     */
    public function diffSeconds(\Zend_Date $date = null, $locale = null)
    {
        $val1 = $this->getUnixTimestamp();
        if (null == $date) {
            $val2 = time();
        } else {
            $val2 = $date->getUnixTimestamp();
        }

        return $val1 - $val2;
    }

    /**
     * The number of weeks in $date subtracted from $this.
     *
     * Zero when both date/times occur in the same week.
     * POSITIVE when $date is YOUNGER than $this
     * Negative when $date is older than $this
     *
     * @param \Zend_Date $date
     * @param \Zend_Locale $locale optional (not used)
     * @return type
     */
    public function diffWeeks(\Zend_Date $date, $locale = null)
    {
        $week1 = clone $this;
        $week2 = clone $date;
        $week1->setWeekDay(1)->setTime(0);
        $week2->setWeekDay(1)->setTime(0);

        $val1 = intval($week1->getUnixTimestamp() / self::WEEK_SECONDS);
        $val2 = intval($week2->getUnixTimestamp() / self::WEEK_SECONDS);

        return $val1 - $val2;
    }

    /**
     * The number of the year in $date subtracted from $this.
     *
     * Zero when both date/times occur in the same year.
     * POSITIVE when $date is YOUNGER than $this
     * Negative when $date is older than $this
     *
     * @param \Zend_Date $date
     * @param \Zend_Locale $locale optional
     * @return type
     */
    public function diffYears(\Zend_Date $date, $locale = null)
    {
        $val1 = intval($this->get(\Zend_Date::YEAR, $locale));
        $val2 = intval($date->get(\Zend_Date::YEAR, $locale));

        return $val1 - $val2;
    }

    /**
     * helper function ot format any date value
     *
     * @param mixed $date ZendDate or somthing that can be turned into a date
     * @param string $outFormat Format string
     * @param string $inFormat Optional formated of the date string
     * @param mixed $localeOut Optional locale for format
     * @return string
     */
    public static function format($date, $outFormat, $inFormat = null, $localeOut = null)
    {
        // \MUtil_Echo::timeFunctionStart(__CLASS__ . '->' . __FUNCTION__);
        if (! $date instanceof \Zend_Date) {
            $date = self::ifDate($date, array($inFormat));

            if (! $date) {
                // \MUtil_Echo::timeFunctionStop(__CLASS__ . '->' . __FUNCTION__);
                return null;
            }
        }

        return $date->toString($outFormat, null, $localeOut);
    }

    /**
     *
     * @param string $date
     * @param array $formats
     * @return \MUtil_Date or null if not a date
     */
    public static function ifDate($date, $formats)
    {
        if (is_string($date)) {
            if (('0' === $date[0]) && ('0' === $date[1])) {
                // Check for empty string dates from the database e.g.: 0000-00-00 00:00:00 or 00-00-0000
                // Yes, this means we cannot use the year 0, but then that does not exists, only -1 AD
                if ((('0' == $date[2]) && ('0' == $date[3])) || ('-' == $date[2])) {
                    return null;
                }
            }
            foreach ((array) $formats as $format) {
                try {
                    if (isset(self::$zendToPhpFormats[$format])) {
                        $phpDate = \DateTime::createFromFormat(self::$zendToPhpFormats[$format], $date);
                        if ($phpDate) {
                            return new self($phpDate);
                        }
                    }
                    if ($format && \Zend_Date::isDate($date, $format)) {
                        return new self($date, $format);
                    }

                } catch (\Exception $ex) {
                    // Ignore on purpose
                }
            }

        } elseif (null === $date) {
            return null;

        } elseif ($date instanceof \DateTime) {
            return new self($date);

        } elseif ($date instanceof \Zend_Date) {
            return $date;

        } else {
            foreach ((array) $formats as $format) {
                try {
                    if (\Zend_Date::isDate($date, $format)) {
                        return new self($date, $format);
                    }
                } catch (\Exception $ex) {
                    // Ignore on purpose
                }
            }
        }

        return null;
    }

    /**
     * Return the day of year of this date as an integer
     *
     * @param mixed $locale optional
     * @return int
     */
    public function intDayOfYear($locale = null)
    {
        return intval($this->get(\Zend_Date::DAY_OF_YEAR, $locale));
    }

    /**
     * Return the month of this date as an integer
     *
     * @param mixed $locale optional
     * @return int
     */
    public function intMonth($locale = null)
    {
        return intval($this->get(\Zend_Date::MONTH, $locale));
    }

    /**
     * Return the week of this date as an integer
     *
     * @param mixed $locale optional
     * @return int
     */
    public function intWeek($locale = null)
    {
        return intval($this->get(\Zend_Date::WEEK, $locale));
    }

    /**
     * Return the year of this date as an integer
     *
     * @param mixed $locale optional
     * @return int
     */
    public function intYear($locale = null)
    {
        return intval($this->get(\Zend_Date::YEAR, $locale));
    }

    /**
     * Returns if the given $date is earlier or equal than this date object (for the datepart)
     *
     * For example:
     * $date = 15.May.2000 <-> $this = 13.June.1999 will return true for day, year and date, but not for month
     *
     * @param  string|integer|array|\Zend_Date $date    Date or datepart that should be earlier or equal
     * @param  string                          $part    OPTIONAL Part of the date to compare, if null the timestamp is used
     * @param  string|\Zend_Locale             $locale  OPTIONAL Locale for parsing input
     * @return boolean
     * @throws \Zend_Date_Exception
     */
    public function isEarlierOrEqual($date, $part = null, $locale = null)
    {
        $result = $this->compare($date, $part, $locale);

        return ($result !== 1);
    }

    /**
     * Returns if the given $date is later or equal than this date object (for the datepart)
     *
     * For example:
     * $date 15.May.2000 <-> $this 13.June.1999 will return true for month but false for day, year and date
     *
     * @param  string|integer|array|\Zend_Date $date    Date or datepart that should be later or equal
     * @param  string                          $part    OPTIONAL Part of the date to compare, if null the timestamp is used
     * @param  string|\Zend_Locale             $locale  OPTIONAL Locale for parsing input
     * @return boolean
     * @throws \Zend_Date_Exception
     */
    public function isLaterOrEqual($date, $part = null, $locale = null)
    {
        $result = $this->compare($date, $part, $locale);

        return ($result !== -1);
    }

    /**
     * Set the time of this object to 23:59:59
     *
     * @return \MUtil_Date (continuation pattern)
     */
    public function setTimeToDayEnd()
    {
        return $this->setTime('23:59:59', 'hh:mm:ss');
    }

    /**
     * Set the time of this object to 00:00:00
     *
     * @return \MUtil_Date (continuation pattern)
     */
    public function setTimeToDayStart()
    {
        return $this->setTime('00:00:00', 'hh:mm:ss');
    }

    /**
     * Returns a string representation of the object
     * Supported format tokens are:
     * G - era, y - year, Y - ISO year, M - month, w - week of year, D - day of year, d - day of month
     * E - day of week, e - number of weekday (1-7), h - hour 1-12, H - hour 0-23, m - minute, s - second
     * A - milliseconds of day, z - timezone, Z - timezone offset, S - fractional second, a - period of day
     *
     * Additionally format tokens but non ISO conform are:
     * SS - day suffix, eee - php number of weekday(0-6), ddd - number of days per month
     * l - Leap year, B - swatch internet time, I - daylight saving time, X - timezone offset in seconds
     * r - RFC2822 format, U - unix timestamp
     *
     * Not supported ISO tokens are
     * u - extended year, Q - quarter, q - quarter, L - stand alone month, W - week of month
     * F - day of week of month, g - modified julian, c - stand alone weekday, k - hour 0-11, K - hour 1-24
     * v - wall zone
     *
     * @param  string              $format  OPTIONAL Rule for formatting output. If null the default date format is used
     * @param  string              $type    OPTIONAL Type for the format string which overrides the standard setting
     * @param  string|Zend_Locale  $locale  OPTIONAL Locale for parsing input
     * @return string
     * /
    public function toString($format = null, $type = null, $locale = null)
    {
        // DOES NOT WORK
        //
        // date() returns an english version
        // strftime() depends on an instable setlocale()
        //
        // DID NOT YET TRY the PHP IntlDateFormatter extension
        \MUtil_Echo::timeFunctionStart(__CLASS__ . '->' . __FUNCTION__);
        if ((null === $locale) && (null == $type) && is_string($format ) && isset(self::$zendToPhpFormats[$format])) {
            \MUtil_Echo::countOccurences('date()');
            $out = date(self::$zendToPhpFormats[$format], $this->getUnixTimestamp());
        } else {
            \MUtil_Echo::countOccurences('toString()');
            \MUtil_Echo::countOccurences($format);
            $out = parent::toString($format, $type, $locale);
        }
        \MUtil_Echo::timeFunctionStop(__CLASS__ . '->' . __FUNCTION__);
        return $out;
    } // */
}
