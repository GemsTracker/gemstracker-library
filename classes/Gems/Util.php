<?php

/**
 *
 * @package    Gems
 * @subpackage Util
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

use IPLib\Factory as IpFactory;
use IPLib\Address\AddressInterface;
use IPLib\Range\RangeInterface;

/**
 * Class for general utility functions and access to general utility classes.
 *
 * @package    Gems
 * @subpackage Util
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Util extends \Gems_Loader_TargetLoaderAbstract
{
    /**
     *
     * @var \Gems_Util_BasePath
     */
    protected $basepath;

    /**
     * Allows sub classes of \Gems_Loader_LoaderAbstract to specify the subdirectory where to look for.
     *
     * @var string $cascade An optional subdirectory where this subclass always loads from.
     */
    protected $cascade = 'Util';

    /**
     *
     * @var \Gems\Util\CommTemplateUtil
     */
    protected $commTemplateUtil;

    /**
     *
     * @var \Gems_Util_DbLookup
     */
    protected $dbLookup;

    /**
     *
     * @var \Gems_Util_Localized
     */
    protected $localized;

    /**
     *
     * @var \Gems\Util\MailJobsUtil
     */
    protected $mailJobsUtil;

    /**
     *
     * @var \Gems\Util\Monitor
     */
    protected $monitor;

    /**
     *
     * @var \Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     *
     * @var \Gems_Util_ReceptionCodeLibrary
     */
    protected $receptionCodeLibrary;

    /**
     *
     * @var \Gems_Util_RequestCache
     */
    protected $requestCache;

    /**
     *
     * @var \Gems_Util_TokenData
     */
    protected $tokenData;

    /**
     *
     * @var \Gems_Util_TrackData
     */
    protected $trackData;

    /**
     *
     * @var \Gems_Util_Translated
     */
    protected $translated;

    /**
     *
     * @return \Gems\Util\CommTemplateUtil
     */
    public function getCommTemplateUtil()
    {
        return $this->_getClass('commTemplateUtil');
    }

    /**
     * Returns a single consent code object.
     *
     * @param string $description
     * @return \Gems\Util\ConsentCode
     */
    public function getConsent($description)
    {
        static $codes = array();

        if (! isset($codes[$description])) {
            $codes[$description] = $this->_loadClass('consentCode', true, array($description));
        }

        return $codes[$description];
    }

    /**
     * Retrieve the consentCODE to use for rejected responses by the survey system
     * The mapping of actual consents to consentCODEs is done in the gems__consents table
     *
     * @return string Default value is 'do not use'
     * @throws \Gems_Exception_Coding
     */
    public function getConsentRejected()
    {
        if ($this->project->offsetExists('consentRejected')) {
            return $this->project->consentRejected;
        }

        // Remove in 1.7.3
        if ($this->project->offsetExists('concentRejected')) {
            throw new \Gems_Exception_Coding('project.ini setting was changed from "concentRejected" to "consentRejected", please update your project.ini');
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
     * Get the code for an unknwon user consent
     *
     * This is de consent description from gems__consents, not the consentCODE
     *
     * @return string
     */
    public function getConsentUnknown()
    {
        return 'Unknown';
    }

    /**
     * Returns the cron job lock
     *
     * @return \Gems_Util_LockFile
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
            $uri = (\MUtil_Https::on() || $this->project->isHttpsRequired()) ? 'https' : 'http';

            $uri .= '://';
            $uri .= isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : $this->project->getConsoleUrl();
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
     * @return \Gems_Util_DbLookup
     */
    public function getDbLookup()
    {
        return $this->_getClass('dbLookup');
    }

    /**
     *
     * @param string $imageFile
     * @return string
     */
    public function getImageUri($imageFile)
    {
        return $this->basepath->getBasePath() . '/' . $this->project->getImageDir() . '/' . $imageFile;
    }

    /**
     *
     * @return \Gems_Util_Localized
     */
    public function getLocalized()
    {
        return $this->_getClass('localized');
    }

    /**
     * Returns the maintenance lock
     *
     * @return \Gems\Util\MailJobsUtil
     */
    public function getMailJobsUtil()
    {
        return $this->_getClass('mailJobsUtil');
    }

    /**
     * Returns the maintenance lock
     *
     * @return \Gems_Util_LockFile
     */
    public function getMaintenanceLock()
    {
        return $this->_loadClass('lockFile', true, array(GEMS_ROOT_DIR . '/var/settings/lock.txt'));
    }

    /**
     * Returns the job monitor
     *
     * @return \Gems\Util\Monitor
     */
    public function getMonitor()
    {
        return $this->_loadClass('Monitor', true);
    }

    /**
     * The organizations whose tokens and tracks are shown for this organization
     *
     * When true: show tokens for all organizations, false: only current organization, array => those organizations
     *
     * @param int $organizationId Optional, uses current user when empty
     * @return boolean|array
     */
    public function getOtherOrgsFor($forOrgId = null)
    {
        // Do not show data from other orgs
        return false;

        // Do show data from all other orgs
        // return true;

        // Return the organizations the user is allowed to see.
        // return array_keys($this->currentUser->getAllowedOrganizations());
    }

    /**
     * Returns a single reception code object.
     *
     * @param string $code
     * @return \Gems_Util_ReceptionCode
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
     * @return \Gems_Util_ReceptionCodeLibrary
     */
    public function getReceptionCodeLibrary()
    {
        return $this->_getClass('receptionCodeLibrary');
    }

    /**
     *
     * @param string  $sourceAction    The action to get the cache from if not the current one.
     * @param boolean $readonly        Optional, tell the cache not to store any new values
     * @return \Gems_Util_RequestCache
     */
    public function getRequestCache($sourceAction = null, $readonly = false)
    {
        return $this->_getClass('requestCache', null, array($sourceAction, $readonly));
    }

    /**
     *
     * @return \Gems_Util_TokenData
     */
    public function getTokenData()
    {
        return $this->_getClass('tokenData');
    }

    /**
     *
     * @return \Gems_Util_TrackData
     */
    public function getTrackData()
    {
        return $this->_getClass('trackData');
    }

    /**
     *
     * @return \Gems_Util_Translated
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
     * Separate with | examples: 10.0.0.0-10.0.0.255, 10.10.*.*, 10.10.151.1 or 10.10.151.1/25
     *
     * @param  string $ip
     * @param  string $ipRanges
     * @return bool
     */
    public function isAllowedIP($ip, $ipRanges = "")
    {
        $address = IpFactory::addressFromString($ip);
        if (! (($address instanceof AddressInterface) && strlen($ipRanges))) {
            return true;
        }
        $aType = $address->getAddressType();

        $ranges = explode('|', $ipRanges);
        foreach ($ranges as $range) {
            if (($sep = strpos($range, '-')) !== false) {
                $rangeIF = IpFactory::rangeFromBoundaries(substr($range, 0, $sep), substr($range, $sep + 1));

            } else {
                $rangeIF = IpFactory::rangeFromString($range);
            }

            if (($rangeIF instanceof RangeInterface) &&
                    $rangeIF->getAddressType() == $aType &&
                    $rangeIF->contains($address)) {
                return true;
            }
        }
        return false;
    }
}
