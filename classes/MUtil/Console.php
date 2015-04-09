<?php

/**
 * Copyright (c) 2013, Erasmus MC
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
 * @subpackage Console
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id: Console.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 * Utilitu class for working with command line applications
 *
 * @package    MUtil
 * @subpackage Console
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.2
 */
class MUtil_Console
{
    /**
     * True when php is running in command line mode
     *
     * @return boolean
     */
    public static function isConsole()
    {
        return !\Zend_Session::$_unitTestEnabled && (PHP_SAPI == 'cli');
    }

    /**
     * Mimics strip_tags but strips the content of certain tags (like script) too
     *
     * Function copied & adapted from a comment in http://www.php.net/manual/en/function.strip-tags.php#97386
     *
     * @param string $s The string to strip
     * @return string
     */
    public static function removeHtml($s)
    {
        $newLinesAfter = 'h1|h2|h3|h4|h5|h6|h7|h8';
        $newLineAfter = 'div|li|p';
        $removeContent = 'script|style|noframes|select|option|link';

        /**///prep the string
        $s = ' ' . $s;

        //begin removal
        /**///remove comment blocks
        while(stripos($s,'<!--') > 0){
            $pos[1] = stripos($s,'<!--');
            $pos[2] = stripos($s,'-->', $pos[1]);
            $len[1] = $pos[2] - $pos[1] + 3;
            $x = substr($s,$pos[1],$len[1]);
            $s = str_replace($x,'',$s);
        }

        /**///remove tags with content between them
        if(strlen($removeContent) > 0){
            $e = explode('|', $removeContent);
            for($i=0;$i<count($e);$i++){
                while(stripos($s,'<' . $e[$i]) > 0){
                    $len[1] = strlen('<' . $e[$i]);
                    $pos[1] = stripos($s,'<' . $e[$i]);
                    $pos[2] = stripos($s,$e[$i] . '>', $pos[1] + $len[1]);
                    $len[2] = $pos[2] - $pos[1] + $len[1];
                    $x = substr($s,$pos[1],$len[2]);
                    $s = str_replace($x,'',$s);
                }
            }
        }

        foreach (explode('|', $newLinesAfter) as $endTag) {
            $s = str_replace("</$endTag>", "\n\n", $s);
        }
        foreach (explode('|', $newLineAfter) as $endTag) {
            $s = str_replace("</$endTag>", "\n", $s);
        }

        /**///remove remaining tags
        $start = 0;
        while(stripos($s,'<', $start) > 0){
            $pos[1] = stripos($s,'<', $start);
            $pos[2] = stripos($s,'>', $pos[1]);
            if (!$pos[2]) {
                //No closing tag! Skip this one
                $start = $pos[1]+1;
            } else {
                $len[1] = $pos[2] - $pos[1] + 1;
                $x = substr($s,$pos[1],$len[1]);
                $s = str_replace($x,'',$s);
            }
        }

        return html_entity_decode(trim($s), ENT_QUOTES, 'cp1252');
    }
}
