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
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    MUtil
 * @subpackage Math
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: Dec.php $
 */

/**
 * Decimal calculation utilities
 *
 * RF:  winbill at hotmail dot com 16-Dec-2010 09:31
 * There are issues around float rounding/flooring,
 * use of intermediate typecasting to string (strval) avoids problems
 *
 * jolyon at mways dot co dot uk 10-Aug-2004 11:41
 * The thing to remember here is that the way a float stores a value makes it
 * very easy for these kind of things to happen. When 79.99 is multiplied
 * by 100, the actual value stored in the float is probably something like
 * 7998.9999999999999999999999999999999999, PHP would print out 7999 when the
 * value is displayed but floor would therefore round this down to 7998.
 *
 *
 * @package    MUtil
 * @subpackage Math
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class MUtil_Dec
{
    /**
     * Get the floor using the specified precision
     *
     * @param float $number
     * @param int $precision
     * @return float
     */
    public static function floor($number, $precision)
    {
        $coefficient = pow(10,$precision);
        return floor(strval($number*$coefficient))/$coefficient;
    }

    /**
     * Get the ceiling using the specified precision
     *
     * @param float $number
     * @param int $precision
     * @return float
     */
    public static function ceil($number, $precision)
    {
        $coefficient = pow(10,$precision);
        return ceil(strval($number*$coefficient))/$coefficient;
    }

}
