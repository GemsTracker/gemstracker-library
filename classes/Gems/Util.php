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
 * @subpackage Util
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Class for general utility functions and access to general utility classes.
 *
 * @package    Gems
 * @subpackage Util
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Util extends Gems_Loader_TargetLoaderAbstract
{
    /**
     *
     * @var Gems_Util_BasePath
     */
    protected $basepath;

    /**
     * Allows sub classes of Gems_Loader_LoaderAbstract to specify the subdirectory where to look for.
     *
     * @var string $cascade An optional subdirectory where this subclass always loads from.
     */
    protected $cascade = 'Util';

    /**
     *
     * @var Gems_Util_DbLookup
     */
    protected $dbLookup;

    /**
     *
     * @var Gems_Util_Localized
     */
    protected $localized;

    /**
     *
     * @var Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     *
     * @var Gems_Util_ReceptionCodeLibrary
     */
    protected $receptionCodeLibrary;

    /**
     *
     * @var Gems_Util_RequestCache
     */
    protected $requestCache;

    /**
     *
     * @var Gems_Util_TokenData
     */
    protected $tokenData;

    /**
     *
     * @var Gems_Util_TrackData
     */
    protected $trackData;

    /**
     *
     * @var Gems_Util_Translated
     */
    protected $translated;

    /**
     * Returns the AccessLogActions
     *
     * @param string $code
     * @return Gems_Util_AccessLogActions
     */
    public function getAccessLogActions()
    {
        return $this->_getClass('accessLogActions', null, array('AccessLogActions'));
    }


    /**
     * Retrieve the consentCODE to use for rejected responses by the survey system
     * The mapping of actual consents to consentCODEs is done in the gems__consents table
     *
     * @return string Default value is 'do not use'
     * @throws Gems_Exception_Coding
     */
    public function getConsentRejected()
    {
        if ($this->project->offsetExists('consentRejected')) {
            return $this->project->consentRejected;
        }

        if ($this->project->offsetExists('concentRejected')) {
            throw new Gems_Exception_Coding('project.ini setting was changed from "concentRejected" to "consentRejected", please update your project.ini');
            return $this->project->concentRejected;
        }

        return 'do not use';
    }

    /**
     * Retrieve the array of possible consentCODEs to use for responses by the survey system
     * The mapping of actual consents to consentCODEs is done in the gems__consents table
     *
     * @return array Default consent codes are 'do not use' and 'consent given'
     */
    public function getConsentTypes()
    {
        if (isset($this->project->consentTypes)) {
            $consentTypes = explode('|', $this->project->consentTypes);
        } else {
            $consentTypes = array('do not use', 'consent given');
        }

        return array_combine($consentTypes, $consentTypes);
    }

    /**
     * Returns the cron job lock
     *
     * @return Gems_Util_LockFile
     */
    public function getCronJobLock()
    {
        return $this->_loadClass('lockFile', true, array(GEMS_ROOT_DIR . '/var/settings/cron_lock.txt'));
    }

    /**
     * Returns the current 'base site' url, optionally with a subpath.
     *
     * @staticvar string $uri
     * @param string $subpath Optional string
     * @return string The Url + basePath plus the optional subpath
     */
    public function getCurrentURI($subpath = '')
    {
        static $uri;

        if (! $uri) {
            $uri = MUtil_Https::on() ? 'https' : 'http';

            $uri .= '://';
            $uri .= $_SERVER['SERVER_NAME'];
            $uri .= $this->basepath->getBasePath();
        }
        if ($subpath && ($subpath[0] != '/')) {
            $subpath = '/' . $subpath;
        }

        return $uri . $subpath;
    }

    /**
     * Get the default user consent
     *
     * This is de consent description from gems__consents, not the consentCODE
     *
     * @return string
     */
    public function getDefaultConsent()
    {
        if (isset($this->project->consentDefault)) {
            return $this->project->consentDefault;
        }

        return 'Unknown';
    }

    /**
     *
     * @return Gems_Util_DbLookup
     */
    public function getDbLookup()
    {
        return $this->_getClass('dbLookup');
    }

    public function getImageUri($imageFile)
    {
        return $this->basepath->getBasePath() . '/' . $this->project->imagedir . '/' . $imageFile;
    }

    /**
     *
     * @return Gems_Util_Localized
     */
    public function getLocalized()
    {
        return $this->_getClass('localized');
    }

    /**
     * Returns the maintenance lock
     *
     * @return Gems_Util_LockFile
     */
    public function getMaintenanceLock()
    {
        return $this->_loadClass('lockFile', true, array(GEMS_ROOT_DIR . '/var/settings/lock.txt'));
    }

    /**
     * Returns a single reception code object.
     *
     * @param string $code
     * @return Gems_Util_ReceptionCode
     */
    public function getReceptionCode($code)
    {
        static $codes = array();

        if (! isset($codes[$code])) {
            $codes[$code] = $this->_loadClass('receptionCode', true, array($code));
        }

        return $codes[$code];
    }

    /**
     * Returns a
     *
     * @return Gems_Util_ReceptionCodeLibrary
     */
    public function getReceptionCodeLibrary()
    {
        return $this->_getClass('receptionCodeLibrary');
    }

    /**
     *
     * @param string  $sourceAction    The action to get the cache from if not the current one.
     * @param boolean $readonly        Optional, tell the cache not to store any new values
     * @return Gems_Util_RequestCache
     */
    public function getRequestCache($sourceAction = null, $readonly = false)
    {
        return $this->_getClass('requestCache', null, array($sourceAction, $readonly));
    }

    /**
     *
     * @return Gems_Util_TokenData
     */
    public function getTokenData()
    {
        return $this->_getClass('tokenData');
    }

    /**
     *
     * @return Gems_Util_TrackData
     */
    public function getTrackData()
    {
        return $this->_getClass('trackData');
    }

    /**
     *
     * @return Gems_Util_Translated
     */
    public function getTranslated()
    {
        return $this->_getClass('translated');
    }

    /**
     * Checks if a given IP is allowed according to a set
     * of IP addresses / ranges.
     *
     * Multiple addresses/ranges are separated by a colon,
     * an individual range takes the form of
     * 10.0.0.0-10.0.0.255 (subnet masks are not supported)
     *
     * @param  string $ip
     * @param  string $ipRanges
     * @return bool
     */
    public function isAllowedIP($ip, $ipRanges = "")
    {
        if (!strlen($ipRanges)) {
            return true;
        }

        $ipLong = ip2long($ip);

        $ranges = explode('|', $ipRanges);

        foreach ($ranges as $range) {
            if (($sep = strpos($range, '-')) !== false) {
                $min = ip2long(substr($range, 0, $sep));
                $max = ip2long(substr($range, $sep + 1));

                if ($min <= $ipLong && $ipLong <= $max) {
                    return true;
                }
            } else {
                if ($ipLong == ip2long($range)) {
                    return true;
                }
            }
        }

        return false;
    }
}
