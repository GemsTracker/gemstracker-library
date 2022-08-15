<?php


/**
 *
 * @package    Gems
 * @subpackage Util
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Util;

use NumberFormatter;
use Gems\Locale\Locale;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Intl\Languages;

/**
 * Localization class, allowing for project specific overrides
 *
 * @package    Gems
 * @subpackage Util
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */
class Localized
{
    /**
     *
     * @var Locale
     */
    protected Locale $locale;

    public function __construct(Locale $locale)
    {
        $this->locale = $locale;
    }

    public function getCountries()
    {
        static $data;

        if (! $data) {
            $data = Countries::getNames($this->locale->getCurrentLanguage());

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
            $languages[$locale] = Languages::getName($locale, $this->locale->getCurrentLanguage());

        }

        asort($languages);

        return $languages;
    }

    public function getMonthName($month)
    {
        static $data;

        if (! $data) {
            $data = \Zend_Locale::getTranslationList('month', $this->locale->getCurrentLanguage());
        }

        $month = intval($month); // Sometimes month comes through as '02'
        if (isset($data[$month])) {
            return $data[$month];
        }
    }

    public function formatNumber(int|float $value, int $precision = 2)
    {
        $formatter = new NumberFormatter($this->locale->getCurrentLanguage(), NumberFormatter::DECIMAL);
        $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, 0);
        $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $precision);
        return $formatter->format($value);
    }
}