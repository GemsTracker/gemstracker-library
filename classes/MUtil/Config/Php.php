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
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

/**
 * 
 * @author Matijs de Jong
 * @package    MUtil
 * @subpackage Config
 * @since 1.0
 * @version 1.0
 * @copyright  Copyright (c) 2010 Erasmus MC (www.erasmusmc.nl) & MagnaFacta (www.magnafacta.nl)
 */

/**
 * 
 * @author Matijs de Jong
 * @package    MUtil
 * @subpackage Config
 * @copyright  Copyright (c) 2010 Erasmus MC (www.erasmusmc.nl) & MagnaFacta (www.magnafacta.nl)
 */
class MUtil_Config_Php
{
    public $infoHtml;
    public $infoStyle;

    public function __construct($what = INFO_ALL, $cleanXHtml = true)
    {
        ob_start();
        phpinfo($what); // INFO_GENERAL & INFO_CONFIGURATION & INFO_MODULES & INFO_ENVIRONMENT & INFO_VARIABLES);
        $info = ob_get_clean();

        $this->infoStyle = self::getTag($info, 'style');
        $this->infoHtml  = self::getTag($info, 'body');
        if ($cleanXHtml) {
            $this->infoHtml  = str_replace(
                array('<font ', '</font>', ' border="0" '),
                array('<span ', '</span>', ' style="border: 0px; " '),
                $this->infoHtml);
        }
    }

    public function getInfo()
    {
        return $this->infoHtml;
    }

    public function getStyle()
    {
        return $this->infoStyle;
    }

    public static function getTag($html, $tag, $includetag = false)
    {
        $p = strpos($html, '<'.$tag.'>');
        if (! $p) {
            $p = strpos($html, '<'.$tag.' ');
        }
        if ($includetag) {
            return substr($html, $p, strpos($html, '</'.$tag.'>', $p) - $p + strlen($tag) + 3);
        } else {
            $p += 2 + strlen($tag);
            return substr($html, $p, strpos($html, '</'.$tag.'>', $p) - $p);
        }
    }
}