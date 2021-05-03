<?php

/**
 *
 * @package    Gems
 * @subpackage Date
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: Period.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

namespace Gems\Date;

/**
 *
 *
 * @package    Gems
 * @subpackage Date
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 8-mei-2015 14:57:15
 */
class Period
{
    /**
     *
     * @param \MUtil_Date $startDate
     * @param string $type
     * @param int $period Can be nefative
     * @return \MUtil_Date
     */
    public static function applyPeriod($startDate, $type, $period)
    {
        if ($startDate instanceof \MUtil_Date) {
            $date = clone $startDate;

            if (self::isDateType($type)) {
                // A 'whole day' period should start at 00:00:00, even when the period is '0'
                $date->setTime(0);
            }

            if ($period) {
                switch (strtoupper($type)) {
                    case 'D':
                        $date->addDay($period);
                        break;

                    case 'H':
                        $date->addHour($period);
                        break;

                    case 'M':
                        $date->addMonth($period);
                        break;

                    case 'N':
                        $date->addMinute($period);
                        break;

                    case 'Q':
                        $date->addMonth($period * 3);
                        break;

                    case 'S':
                        $date->addSecond($period);
                        break;

                    case 'W':
                        $date->addDay($period * 7);
                        break;

                    case 'Y':
                        $date->addYear($period);
                        break;

                    default:
                        throw new \Gems_Exception_Coding('Unknown period type; ' . $type);

                }
            }
            return $date;
        }
    }

    /**
     * @param $type One letter
     * @return bool True when whole date
     */
    public static function isDateType($type)
    {
        switch (strtoupper($type)) {
            case 'S':
            case 'N':
            case 'H':
                return false;

            default:
                // A 'whole day' period should start at 00:00:00, even when the period is '0'
                return true;
        }
    }
}
