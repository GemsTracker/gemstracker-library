<?php

/**
 * Copyright (c) 2015, Erasmus MC
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
 * DISCLAIMED. IN NO EVENT SHALL MAGNAFACTA BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
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

            switch (strtoupper($type)) {
                case 'S':
                case 'N':
                case 'H':
                    break;

                default:
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
}
