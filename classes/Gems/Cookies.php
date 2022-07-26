<?php

/**
 *
 * @package    Gems
 * @subpackage Cookies
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems;

/**
 * Static \Gems cookie utilities
 *
 * @package    Gems
 * @subpackage Cookies
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Cookies
{
    const LOCALE_COOKIE       = 'gems_locale';
    const ORGANIZATION_COOKIE = 'gems_organization';

    /**
     * Get a specific cookie from the request.
     *
     * @param \Zend_Controller_Request_Abstract $request
     * @param string $name
     * @param mixed $default
     * @return mixed Cookie value
     */
    public static function get(\Zend_Controller_Request_Abstract $request, $name, $default = null)
    {
        return $request->getCookie($name, $default);
    }

    /**
     * Get the current locale from the cookie.
     *
     * @param \Zend_Controller_Request_Abstract $request
     * @return string The current locale
     */
    public static function getLocale(\Zend_Controller_Request_Abstract $request)
    {
        return self::get($request, self::LOCALE_COOKIE);
    }

    /**
     * Get the current organization from the cookie.
     *
     * @param \Zend_Controller_Request_Abstract $request
     * @return int The current organization
     */
    public static function getOrganization(\Zend_Controller_Request_Abstract $request)
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
        // \Gems uses the empty string when the base path is '/'
        if (! $basepath) {
            $basepath = '/';
        }

        if (\Zend_Session::$_unitTestEnabled) {
            return true;
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
