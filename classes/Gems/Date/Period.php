<?php

/**
 *
 * @package    Gems
 * @subpackage Date
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Date;

use DateInterval;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;

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
     * @param ?DateTimeInterface $startDate
     * @param string $type
     * @param int $period Can be negative
     * @return ?DateTimeInterface
     */
    public static function applyPeriod($startDate, $type, $period): ?DateTimeInterface
    {
        if (! $startDate instanceof DateTimeInterface) {
            return null;
        }
        
        $date = DateTimeImmutable::createFromInterface($startDate);

        if (self::isDateType($type)) {
            // A 'whole day' period should start at 00:00:00, even when the period is '0'
            $date = $date->setTime(0, 0, 0);
        }
        
        if ($period) {
            switch (strtoupper($type)) {
                case 'Q':
                    $periodString = 'P' . ($period * 3) . 'M';
                    break;

                case 'N':
                    $periodString = 'PT' . $period . 'M;';
                    break;
                    
                case 'H':
                case 'S':
                    $periodString = 'PT' . $period . strtoupper($type);
                    break;

                case 'D':
                case 'M':
                case 'W':
                case 'Y':
                    $periodString = 'P' . $period . strtoupper($type);
                    break;

                default:
                    throw new \Gems\Exception\Coding('Unknown period type; ' . $type);

            }
            return $date->add(new DateInterval($periodString));
        }

        return $date;
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
