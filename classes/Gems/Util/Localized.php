<?php


/**
 *
 * @package    Gems
 * @subpackage Util
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Localization class, allowing for project specific overrides
 *
 * @package    Gems
 * @subpackage Util
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */
class Gems_Util_Localized extends \Gems_Registry_TargetAbstract
{
    /**
     *
     * @var \Zend_Locale
     */
    protected $locale;

    /**
     *
     * @var \Gems_Project_ProjectSettings
     */
    protected $project;

    public function getCountries()
    {
        static $data;

        if (! $data) {
            $data = \Zend_Locale::getTranslationList('territory', $this->locale, 2);

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
            $languages[$locale] = \Zend_Locale::getTranslation($locale, 'Language', $this->locale);
        }

        asort($languages);

        return $languages;
    }

    public function getMonthName($month)
    {
        static $data;

        if (! $data) {
            $data = \Zend_Locale::getTranslationList('month', $this->locale);
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

            if ($descr = \Zend_Locale_Data::getContent($locale, 'relative', $days)) {
                $recentTime = $showRecentTime ? ' ' . date('H:i', $dateTime) : '';
                return $descr . $recentTime;
            }

            $daysDescr = '';
            if (($days > -14) && ($days < 14)) {
                if ($dayFormat = \Zend_Locale_Data::getContent($locale, 'unit', array('day', (abs($days) == 1) ? 'one' : 'other'))) {
                    $daysDescr = ' ' . str_replace('{0}', $days, $dayFormat);
                }
            }

            $date = new \Zend_Date($dateTimeValue);
            return $date->toString($locale);
            // return date('d-m-Y', $dateTime) . $daysDescr;
        }

        return null;
    } // */

    public function formatNumber($value, $precision = 2)
    {
        if (null !== $value) {
            return \Zend_Locale_Format::toNumber($value, array('precision' => $precision, 'locale' => $this->locale));
        }
    }
}