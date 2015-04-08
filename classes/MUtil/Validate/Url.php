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
 * @package    MUtil
 * @subpackage Validate
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 * @package    MUtil
 * @subpackage Validate
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.0
 */
class MUtil_Validate_Url extends \Zend_Validate_Abstract
{
    /**
     * Error constants
     */
    const ERROR_SITE_NOT_FOUND = 'siteNotFound';
    const ERROR_URL_NOT_VALID = 'urlNotFound';

    /**
     * @var array Message templates
     */
    protected $_messageTemplates = array(
        self::ERROR_SITE_NOT_FOUND => 'The site %value%" does not exist',
        self::ERROR_URL_NOT_VALID  => '"%value%" is not a valid url',
        \Zend_Validate_Hostname::INVALID                 => "Invalid type given, value should be a string",
        \Zend_Validate_Hostname::IP_ADDRESS_NOT_ALLOWED  => "'%value%' appears to be an IP address, but IP addresses are not allowed",
        \Zend_Validate_Hostname::UNKNOWN_TLD             => "'%value%' appears to be a DNS hostname but cannot match TLD against known list",
        \Zend_Validate_Hostname::INVALID_DASH            => "'%value%' appears to be a DNS hostname but contains a dash (-) in an invalid position",
        \Zend_Validate_Hostname::INVALID_HOSTNAME_SCHEMA => "'%value%' appears to be a DNS hostname but cannot match against hostname schema for TLD '%tld%'",
        \Zend_Validate_Hostname::UNDECIPHERABLE_TLD      => "'%value%' appears to be a DNS hostname but cannot extract TLD part",
        \Zend_Validate_Hostname::INVALID_HOSTNAME        => "'%value%' does not match the expected structure for a DNS hostname",
        \Zend_Validate_Hostname::INVALID_LOCAL_NAME      => "'%value%' does not appear to be a valid local network name",
        \Zend_Validate_Hostname::LOCAL_NAME_NOT_ALLOWED  => "'%value%' appears to be a local network name but local network names are not allowed",
        \Zend_Validate_Hostname::CANNOT_DECODE_PUNYCODE  => "'%value%' appears to be a DNS hostname but the given punycode notation cannot be decoded"
    );

    /**
     * Returns true if and only if $value meets the validation requirements
     *
     * If $value fails validation, then this method returns false, and
     * getMessages() will return an array of messages that explain why the
     * validation failed.
     *
     * @param  mixed $value
     * @return boolean
     * @throws \Zend_Valid_Exception If validation of $value is impossible
     */
    public function isValid($value, $context = array())
    {
        $this->_setValue($value);

        if ($value) {
            try {
                $uri = \Zend_Uri::factory($value);

                // Check the host against the allowed values; delegated to \Zend_Filter.
                $validate = new \Zend_Validate_Hostname(\Zend_Validate_Hostname::ALLOW_DNS | \Zend_Validate_Hostname::ALLOW_IP | \Zend_Validate_Hostname::ALLOW_LOCAL);

                if (! $validate->isValid($uri->getHost())) {
                    foreach ($validate->getMessages() as $key => $msg) {
                        $this->_error($key);
                    }
                    return false;
                }

                if (function_exists('curl_init')) {
                    $ch = curl_init($value);

                    if (false === $ch) {
                        $this->_error(self::ERROR_URL_NOT_VALID);
                        return false;
                    }

                    // Authentication
                    // if ($usr) {
                        // curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                        // curl_setopt($ch, CURLOPT_USERPWD, $usr.':'.$pwd);
                    // }

                    // curl_setopt($ch, CURLOPT_FILETIME, true);
                    curl_setopt($ch, CURLOPT_NOBODY, true);

                    /**
                     * @todo Unknown CA's should probably be imported...
                     */
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

                    $valid = curl_exec($ch);
                    if (! $valid) {
                        $this->_error(self::ERROR_SITE_NOT_FOUND);
                    }

                    // $return = curl_getinfo($ch, CURLINFO_FILETIME);
                    // \MUtil_Echo::r('Date at server: '.date('r', $return));

                    curl_close($ch);

                    return $valid;

                } else {
                    return true;
                }

            } catch (Exception $e) {
                $this->_error(self::ERROR_URL_NOT_VALID);
                $this->setMessage($e->getMessage(), self::ERROR_URL_NOT_VALID);

                return false;
            }
        }
    }
}
