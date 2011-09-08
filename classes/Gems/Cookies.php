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
 * 
 * @author     Matijs de Jong
 * @since      1.0
 * @version    $Id: Cookies.php 345 2011-07-28 08:39:24Z 175780 $
 * @package    Gems
 * @subpackage Cookies
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

/**
 * Static Gems cookie utilities
 * 
 * @author     Matijs de Jong
 * @package    Gems
 * @subpackage Cookies
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */
class Gems_Cookies
{
    const LOCALE_COOKIE = 'gems_locale';

    public static function get(Zend_Controller_Request_Abstract $request, $name)
    {
        return $request->getCookie($name);
    }

    public static function getLocale(Zend_Controller_Request_Abstract $request)
    {
        return self::get($request, self::LOCALE_COOKIE);
    }

    public static function set($name, $value, $days = 30, $basepath = '/')
    {
        // Gems uses the empty string when the base path is '/'
        if (! $basepath) {
            $basepath = '/';
        }

        // Set the cookie for 30 days
        return setcookie($name, $value, time() + ($days * 86400), $basepath);
    }

    public static function setLocale($locale, $basepath = '/')
    {
        // Set the cookie for 30 days
        return self::set(self::LOCALE_COOKIE, $locale, 30, $basepath);
    }
}
