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
use DateTimeImmutable;
use DateTimeInterface;
use Gems\Exception\Coding;

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
    public static function applyPeriod(DateTimeInterface|null $startDate, string $type, int $period): ?DateTimeInterface
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
                    $periodString = 'P' . abs($period * 3) . 'M';
                    break;

                case 'N':
                    $periodString = 'PT' . abs($period) . 'M';
                    break;
                    
                case 'H':
                case 'S':
                    $periodString = 'PT' . abs($period) . strtoupper($type);
                    break;

                case 'D':
                case 'M':
                case 'W':
                case 'Y':
                    $periodString = 'P' . abs($period) . strtoupper($type);
                    break;

                default:
                    throw new Coding('Unknown period type: ' . $type);

            }
            $interval = new DateInterval($periodString);
            if ($period < 0) {
                $interval->invert = 1;
            }
            return $date->add($interval);
        }

        return $date;
    }

    /**
     * @param string $type One letter
     * @return bool True when whole date
     */
    public static function isDateType(string $type): bool
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
