<?php

/**
 * Copyright (c) 2012, Erasmus MC
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
 *
 * @package    MUtil
 * @subpackage String
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $id: String.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 * A collection of static string utility functions
 *
 * @package    MUtil
 * @subpackage String
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class MUtil_String
{
    /**
     * Return the part of $input before any of the $charFilter characters
     *
     * beforeChars('abcdef', 'dgi') => 'abc'
     *
     * beforeChars('abcdef', 'xyz') => 'abcdef'
     *
     * @param string $input The text to return part of
     * @param string $charFilter The characters to filter on
     * @return string
     */
    public static function beforeChars($input, $charFilter)
    {
        $rest = strpbrk($input, $charFilter);

        if ($rest === false) {
            return $input;
        }

        return substr($input, 0, -strlen($rest));
    }

    /**
     * Return the part after $input and $filter stopped being the same
     *
     * stripStringLeft('abcdef', 'abcx') => 'def'
     *
     * stripStringLeft('abcdef', 'def') => 'abcdef'
     *
     * @param string $input The text to return part of
     * @param string $filter The text to filter on
     * @return string
     */
    public static function stripStringLeft($input, $filter)
    {
        $count = min(array(strlen($input), strlen($filter)));

        for ($i = 0; $i < $count; $i++) {
            if ($input[$i] != $filter[$i]) {
                break;
            }
        }

        return substr($input, $i);
    }
}
