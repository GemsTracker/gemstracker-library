<?php

/**
 *
 * @package    Gems
 * @subpackage Util
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

/**
 * Provides translated strings for default options like gender and takes care of date/time formatting
 *
 * @package    Gems
 * @subpackage Util
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */
class Gems_Util_Translated
{
    use \MUtil\Translate\TranslateableTrait;

    /**
     * Format string usaed by this project date output to site users
     *
     * @var string
     */
    protected $phpDateFormatString = 'd-m-Y';

    /**
     * Format string usaed by this project date time output to site users
     *
     * @var string
     */
    protected $phpDateTimeFormatString = 'd-m-Y H:i';

    /**
     * Format string usaed by this project time output to site users
     *
     * @var string
     */
    protected $phpTimeFormatString = 'H:i';

    /**
     * Date format string used by this project
     *
     * @var string
     */
    public $dateFormatString = 'yyyy-MM-dd';

    /**
     * DateTime format string used by this project
     *
     * @var string
     */
    public $dateTimeFormatString = 'yyyy-MM-dd HH:mm:ss';

    /**
     * Array representing an empty choice
     *
     * @var array
     */
    public static $emptyDropdownArray;

    public function __construct(\MUtil\Translate\Translator $translator)
    {
        $this->translate = $translator;

        self::$emptyDropdownArray = ['' => $this->_('-')];
    }

    /**
     * Returns a callable if a method is called as a variable
     *
     * @param string $name
     * @return Callable
     */
    public function __get($name)
    {
        if (method_exists($this, $name)) {
            // Return a callable
            return array($this, $name);
        }

        throw new \Gems_Exception_Coding("Unknown method '$name' requested as callable.");
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
    }

    /**
     * Get a readable version of date / time object with nearby days translated in text
     *
     * @param \MUtil_Date $dateTimeValue
     * @return string
     */
    public function describeDateFromNow($dateTimeValue)
    {
        if (! $dateTimeValue) {
            return null;
        }

        if ($dateTimeValue instanceof \MUtil_Date) {
            $dateTime = $dateTimeValue;
        } else{
            $dateTime = \MUtil_Date::ifDate(
                $dateTimeValue,
                array(\Gems_Tracker::DB_DATETIME_FORMAT, \Gems_Tracker::DB_DATE_FORMAT, \Zend_Date::ISO_8601)
            );
            if (! $dateTime) {
                return null;
            }
        }
        $days = $dateTime->diffDays();

        switch ($days) {
            case -2:
                return $this->_('2 days ago');

            case -1:
                return $this->_('Yesterday');

            case 0:
                return $this->_('Today');

            case 1:
                return $this->_('Tomorrow');

            case 2:
                return $this->_('Over 2 days');

            default:
                if (($days > -14) && ($days < 14)) {
                    if ($days > 0) {
                        return sprintf($this->_('Over %d days'), $days);
                    } else {
                        return sprintf($this->_('%d days ago'), -$days);
                    }
                }

                return $dateTime->getDateTime()->format($this->phpDateFormatString);
        }
    }

    /**
     * Get a readable version of date / time object with nearby time translated in text
     *
     * @param \MUtil_Date $dateTimeValue
     * @return string
     */
    public function describeTimeFromNow($dateTimeValue)
    {
        if (! $dateTimeValue) {
            return null;
        }

        if ($dateTimeValue instanceof \MUtil_Date) {
            $dateTime = $dateTimeValue;
        } else{
            $dateTime = \MUtil_Date::ifDate(
                $dateTimeValue,
                array(\Gems_Tracker::DB_DATETIME_FORMAT, \Gems_Tracker::DB_DATE_FORMAT, \Zend_Date::ISO_8601)
            );
            if (! $dateTime) {
                return null;
            }
        }

        $hours = $dateTime->diffHours();

        if (abs($hours) < 49) {
            if ($hours > 1) {
                return sprintf($this->_('In %d hours'), $hours);
            }
            if ($hours < -1) {
                return sprintf($this->_('%d hours ago'), -$hours);
            }

            // Switch to minutes
            $minutes = $dateTime->diffMinutes();
            if ($minutes > 0) {
                return sprintf($this->plural('In %d minute', 'In %d minutes', $minutes), $minutes);
            }
            if ($minutes < 0) {
                return sprintf($this->plural('%d minute ago', '%d minutes ago', $minutes), -$minutes);
            }

            return $this->_('this minute!');
        }

        return $dateTime->getDateTime()->format($this->phpDateTimeFormatString);
    }

    /**
     * Get a readable version of date / time object with nearby days translated in text
     *
     * @param \MUtil_Date $dateValue
     * @return string|\MUtil_Html_HtmlElement
     */
    public function formatDate($dateValue)
    {
        return $this->describeDateFromNow($dateValue);
    }

    /**
     * Get a readable version of date / time object with nearby days translated in text
     * or 'forever' when null
     *
     * @param \MUtil_Date $dateValue
     * @return string|\MUtil_Html_HtmlElement
     */
    public function formatDateForever($dateValue)
    {
        if ($dateValue) {
            return $this->describeDateFromNow($dateValue);
        } else {
            return \MUtil_Html::create()->span($this->_('forever'), array('class' => 'disabled'));
        }
    }

    /**
     * Get a readable version of date / time object with nearby days translated in text
     * or 'n/a' when null
     *
     * @param \MUtil_Date $dateValue
     * @return string|\MUtil_Html_HtmlElement
     */
    public function formatDateNa($dateValue)
    {
        if ($dateValue) {
            return $this->describeDateFromNow($dateValue);
        } else {
            return \MUtil_Html::create()->span($this->_('n/a'), array('class' => 'disabled'));
        }
    }

    /**
     * Get a readable version of date / time object with nearby days translated in text
     * or 'never' when null
     *
     * @param \MUtil_Date $dateValue
     * @return string|\MUtil_Html_HtmlElement
     */
    public function formatDateNever($dateValue)
    {
        if ($dateValue) {
            return $this->describeDateFromNow($dateValue);
        } else {
            return \MUtil_Html::create()->span($this->_('never'), array('class' => 'disabled'));
        }
    }

    /**
     * Get a readable version of date / time object with nearby days translated in text
     *
     * @param \MUtil_Date $dateTimeValue
     * @param string $format Optioanl PHP date() format
     * @return string
     * @deprecated since version 1.9.1, replaced by describeDateFromNow
     */
    public function formatDateTime($dateTimeValue, $format = null)
    {
        return $this->describeDateFromNow($dateTimeValue);
    }

    /**
     * Get a readable version of date / time object with nearby days translated in text
     * or 'forever' when null
     *
     * @param \MUtil_Date $dateValue
     * @return string|\MUtil_Html_HtmlElement
     */
    public function formatDateTimeForever($dateValue)
    {
        if ($dateValue) {
            return $this->describeTimeFromNow($dateValue);
        } else {
            return \MUtil_Html::create()->span($this->_('forever'), array('class' => 'disabled'));
        }
    }

    /**
     * Get a readable version of date / time object with nearby days translated in text
     * or 'n/a' when null
     *
     * @param \MUtil_Date $dateValue
     * @return string|\MUtil_Html_HtmlElement
     */
    public function formatDateTimeNa($dateValue)
    {
        if ($dateValue) {
            return $this->describeTimeFromNow($dateValue);
        } else {
            return \MUtil_Html::create()->span($this->_('n/a'), array('class' => 'disabled'));
        }
    }

    /**
     * Get a readable version of date / time object with nearby days translated in text
     * or 'never' when null
     *
     * @param \MUtil_Date $dateValue
     * @return string|\MUtil_Html_HtmlElement
     */
    public function formatDateTimeNever($dateValue)
    {
        if ($dateValue) {
            return $this->describeTimeFromNow($dateValue);
        } else {
            return \MUtil_Html::create()->span($this->_('never'), array('class' => 'disabled'));
        }
    }

    /**
     * Get a readable version of date / time object with nearby days translated in text
     * or 'unknown' when null
     *
     * @param \MUtil_Date $dateValue
     * @return string|\MUtil_Html_HtmlElement
     */
    public function formatDateTimeUnknown($dateValue)
    {
        if ($dateValue) {
            return $this->describeTimeFromNow($dateValue);
        } else {
            return \MUtil_Html::create()->span($this->_('unknown'), array('class' => 'disabled'));
        }
    }

    /**
     * Get a readable version of date / time object with nearby days translated in text
     * or 'unknown' when null
     *
     * @param \MUtil_Date $dateValue
     * @return string|\MUtil_Html_HtmlElement
     */
    public function formatDateUnknown($dateValue)
    {
        if ($dateValue) {
            return $this->describeDateFromNow($dateValue);
        } else {
            return \MUtil_Html::create()->span($this->_('unknown'), array('class' => 'disabled'));
        }
    }

    /**
     * Returns the time in seconds as a display string
     *
     * @param int $dateTimeValue
     * @return string
     */
    public function formatTime($dateTimeValue)
    {
        if ($dateTimeValue instanceof \Zend_Date) {
            $dateTimeValue = $dateTimeValue->getTimestamp();
        }
        $seconds = str_pad($dateTimeValue % 60, 2, '0', STR_PAD_LEFT);
        $rest    = intval($dateTimeValue / 60);
        $minutes = str_pad($rest % 60, 2, '0', STR_PAD_LEFT);
        $hours   = intval($rest / 60);
        $days    = intval($hours / 24);

        if ($hours > 48) {
            $hours = $hours % 24;

            return sprintf($this->_('%d days %d:%s:%s'), $days, $hours, $minutes, $seconds);
        } elseif ($hours) {
            return sprintf($this->_('%d:%s:%s'), $hours, $minutes, $seconds);
        } else {
            return sprintf($this->_('%d:%s'), $minutes, $seconds);
        }
    }

    /**
     * Returns the time in seconds as a display string or unknown when null
     *
     * @param int $dateTimeValue
     * @return string
     */
    public function formatTimeUnknown($dateTimeValue)
    {
        if (null === $dateTimeValue) {
            return \MUtil_Html::create()->span($this->_('unknown'), array('class' => 'disabled'));
        } else {
            return $this->formatTime($dateTimeValue);
        }
    }

    /**
     * The date calculation versus manual set
     *
     * @return array
     */
    public function getDateCalculationOptions()
    {
        return array(0 => $this->_('Calculated'), 1 => $this->_('Manually'));
    }

    /**
     * Get a translated empty value for usage in dropdowns
     *
     * On instantiation of the class via \Gems_Loader this variable will be populated
     * in checkRegistryRequestsAnswers
     *
     * @return array
     */
    public function getEmptyDropdownArray()
    {
        return self::$emptyDropdownArray;
    }

    /**
     * Returns the functional description of a gender for use in e.g. interface elements
     *
     * @param string $locale
     * @return array gender => string
     */
    public function getGenders($locale = null)
    {
        return array('M' => $this->_('Male',locale: $locale), 'F' => $this->_('Female',locale: $locale), 'U' => $this->_('Unknown',locale: $locale));
    }

    /**
     * Returns the gender for use in dear mr./mrs. display
     *
     * In practice: starts uppercase
     *
     * @param string $locale
     * @return array gender => string
     */
    public function getGenderDear($locale = null)
    {
        return array('M' => $this->_('Dear mr.',locale: $locale), 'F' => $this->_('Dear mrs.',locale: $locale), 'U' => $this->_('Dear mr./mrs.',locale: $locale));
    }

    /**
     * Returns the gender for use as part of a sentence, e.g. Dear Mr/Mrs
     *
     * In practice: starts lowercase
     *
     * @param string $locale
     * @return array gender => string
     */
    public function getGenderGreeting($locale = null)
    {
        return array('M' => $this->_('mr.',locale: $locale), 'F' => $this->_('mrs.',locale: $locale), 'U' => $this->_('mr./mrs.',locale: $locale));
    }

    /**
     * Returns the gender for use in stand-alone name display
     *
     * In practice: starts uppercase
     *
     * @param string $locale
     * @return array gender => string
     */
    public function getGenderHello($locale = null)
    {
        return array('M' => $this->_('Mr.', locale: $locale), 'F' => $this->_('Mrs.', locale: $locale), 'U' => $this->_('Mr./Mrs.', locale: $locale));
    }

    /**
     * Get an array of translated labels for the date period units
     *
     * @return array date_unit => label
     */
    public function getPeriodUnits()
    {
        return array(
            // 'S' => $this->_('Seconds'),
            'N' => $this->_('Minutes'),
            'H' => $this->_('Hours'),
            'D' => $this->_('Days'),
            'W' => $this->_('Weeks'),
            'M' => $this->_('Months'),
            'Q' => $this->_('Quarters'),
            'Y' => $this->_('Years')
        );
    }

    /**
     * Yes / no values array
     *
     * @staticvar array $data
     * @return array 1 => Yes, 0 => No
     */
    public function getYesNo()
    {
        static $data;

        if (! $data) {
            $data = array(1 => $this->_('Yes'), 0 => $this->_('No'));
        }

        return $data;
    }

    /**
     * Mark empty data as empty
     *
     * @param string $subject
     * @return mixed
     */
    public function markEmpty($value)
    {
        if (empty($value)) {
            $em = \MUtil_Html::create('em');
            $em->raw($this->_('&laquo;empty&raquo;'));

            return $em;
        }

        return $value;
    }

}
