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
 *
 * @package    Gems
 * @subpackage Cookies
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Static Gems cookie utilities
 *
 * @package    Gems
 * @subpackage Cookies
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Cookies
{
    const LOCALE_COOKIE       = 'gems_locale';
    const ORGANIZATION_COOKIE = 'gems_organization';

    /**
     * Get a specific cookie from the request.
     *
     * @param Zend_Controller_Request_Abstract $request
     * @param string $name
     * @param mixed $default
     * @return mixed Cookie value
     */
    public static function get(Zend_Controller_Request_Abstract $request, $name, $default = null)
    {
        return $request->getCookie($name, $default);
    }

    /**
     * Get the current locale from the cookie.
     *
     * @param Zend_Controller_Request_Abstract $request
     * @return string The current locale
     */
    public static function getLocale(Zend_Controller_Request_Abstract $request)
    {
        return self::get($request, self::LOCALE_COOKIE);
    }

    /**
     * Get the current organization from the cookie.
     *
     * @param Zend_Controller_Request_Abstract $request
     * @return int The current organization
     */
    public static function getOrganization(Zend_Controller_Request_Abstract $request)
    {
        return intval(self::get($request, self::ORGANIZATION_COOKIE));
    }

    /**
     * Store this cookie in a generic save method that works for both sub-directory
     * installations and own url installations.
     *
     * @param string $name Name of the cookie
     * @param mixed $value Value to set
     * @param int $days Number of days to keep this cookie
     * @param string $basepath The folder of the domain, if any.
     * @return boolean True if the cookie was stored.
     */
    public static function set($name, $value, $days = 30, $basepath = '/')
    {
        // Gems uses the empty string when the base path is '/'
        if (! $basepath) {
            $basepath = '/';
        }

        // Set the cookie for 30 days
        return setcookie($name, $value, time() + ($days * 86400), $basepath, '', (APPLICATION_ENV == 'production'), true);
    }

    /**
     * Store the locale in a cookie.
     *
     * @param string $locale Locale to store
     * @param string $basepath The folder of the domain, if any.
     * @return boolean True if the cookie was stored.
     */
    public static function setLocale($locale, $basepath = '/')
    {
        // Set the cookie for 30 days
        return self::set(self::LOCALE_COOKIE, $locale, 30, $basepath);
    }

    /**
     * Store the organization in a cookie.
     *
     * @param int $organization Organization to store
     * @param string $basepath The folder of the domain, if any.
     * @return boolean True if the cookie was stored.
     */
    public static function setOrganization($organization, $basepath = '/')
    {
        if ($organization) {
            // Set the cookie for 30 days
            return self::set(self::ORGANIZATION_COOKIE, $organization, 30, $basepath);
        }
    }
}
