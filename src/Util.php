<?php

/**
 *
 * @package    Gems
 * @subpackage Util
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems;

use Gems\Repository\ReceptionCodeRepository;
use Gems\Tracker\ReceptionCode;
use Gems\User\User;
use IPLib\Address\AddressInterface;
use IPLib\Factory as IpFactory;
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
class Util extends \Gems\Loader\TargetLoaderAbstract
{
    /**
     *
     * @var \Gems\Util\BasePath
     */
    protected $basepath;

    /**
     * Allows sub classes of \Gems\Loader\LoaderAbstract to specify the subdirectory where to look for.
     *
     * @var string $cascade An optional subdirectory where this subclass always loads from.
     */
    protected ?string $cascade = 'Util';

    /**
     *
     * @var \Gems\Util\CommTemplateUtil
     */
    protected $commTemplateUtil;

    protected $config;

    /**
     * @var User
     */
    protected $currentUser;

    /**
     *
     * @var \Gems\Util\DbLookup
     */
    protected $dbLookup;

    /**
     *
     * @var \Gems\Util\Localized
     */
    protected $localized;

    /**
     *
     * @var \Gems\Util\CommJobsUtil
     */
    protected $mailJobsUtil;

    /**
     *
     * @var \Gems\Util\Monitor\Monitor
     */
    protected $monitor;

    /**
     *
     * @var \Gems\Project\ProjectSettings
     */
    protected $project;

    /**
     *
     * @var \Gems\Util\TokenData
     */
    protected $tokenData;

    /**
     *
     * @var \Gems\Util\TrackData
     */
    protected $trackData;

    /**
     *
     * @var \Gems\Util\Translated
     */
    protected $translated;

    /**
     * Returns the maintenance lock
     *
     * @return \Gems\Util\CommJobsUtil
     */
    public function getCommJobsUtil()
    {
        return $this->_getClass('commJobsUtil');
    }

    /**
     * Returns the maintenance lock
     *
     * @return \Gems\Util\CommMessengersUtil
     */
    public function getCommMessengersUtil()
    {
        return $this->_getClass('commMessengersUtil');
    }

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
     * @deprecated Use ConsentRepoositry
     */
    public function getConsent($description)
    {
        static $codes = array();

        if (! isset($codes[$description])) {
            $codes[$description] = $this->_loadClass('ConsentCode', true, array($description));
        }

        return $codes[$description];
    }

    /**
     * Retrieve the consentCODE to use for rejected responses by the survey system
     * The mapping of actual consents to consentCODEs is done in the gems__consents table
     *
     * @return string Default value is 'do not use'
     * @throws \Gems\Exception\Coding
     */
    public function getConsentRejected()
    {
        if ($this->project->offsetExists('consentRejected')) {
            return $this->project->consentRejected;
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
     * @return \Gems\Util\LockFile
     */
    public function getCronJobLock()
    {
        return $this->getLockFile('cron_lock.txt');
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
            $uri = (\MUtil\Https::on() || $this->project->isHttpsRequired()) ? 'https' : 'http';

            $uri .= '://';
            if (isset($_SERVER['SERVER_NAME'])) {
                $uri .= $_SERVER['SERVER_NAME'];
                $uri .= $this->basepath->getBasePath();
            } else {
                // I did not want to add loader to util, can no longer tell why
                $org = $this->currentUser->getCurrentOrganization();

                if ($org instanceof \Gems\User\Organization) {
                    $uri = $org->getPreferredSiteUrl();
                } else {
                    throw new \Gems\Exception\Coding(
                        __CLASS__ . '->' . __FUNCTION__ . "() should not be called when there is no current organization."
                    );
                }
            }
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
     * @return \Gems\Util\DbLookup
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
     * @return \Gems\Util\Localized
     */
    public function getLocalized()
    {
        return $this->_getClass('localized');
    }

    /**
     * Returns a lock object
     *
     * @param string $filename (without a path!)
     * @return \Gems\Util\LockFile
     */
    public function getLockFile($filename)
    {
        return $this->_loadClass('lockFile', true, array($this->config['rootDir'] . '/var/settings/' . $filename));
    }

    /**
     * Returns the maintenance lock
     *
     * @return \Gems\Util\LockFile
     */
    public function getMaintenanceLock()
    {
        return $this->getLockFile('lock.txt');
    }

    /**
     * Returns the job monitor
     *
     * @return \Gems\Util\Monitor\Monitor
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
     * @param int $forOrgId Optional, uses current user when empty
     * @return boolean|array
     */
    public function getOtherOrgsFor($forOrgId = null)
    {
        // Do not show data from other orgs
        return [];

        // Do show data from all other orgs
        // return true;

        // Return the organizations the user is allowed to see.
        // return array_keys($this->currentUser->getAllowedOrganizations());
    }

    /**
     * Returns a single reception code object.
     *
     * @param string $code
     * @return ReceptionCode
     */
    public function getReceptionCode($code)
    {
        static $codes = array();

        if (! isset($codes[$code])) {
            $receptionCodeRepository = $this->getReceptionCodeRepository();
            $receptionCodeRepository->getReceptionCode($code);
        }

        return $codes[$code];
    }

    /**
     * Returns a
     *
     * @return \Gems\Util\ReceptionCodeLibrary
     */
    public function getReceptionCodeLibrary()
    {
        return $this->containerLoad('receptionCodeLibrary');
    }

    public function getReceptionCodeRepository(): ReceptionCodeRepository
    {
        return $this->containerLoad(ReceptionCodeRepository::class);
    }

    /**
     *
     * @return \Gems\Util\TokenData
     */
    public function getTokenData()
    {
        return $this->_getClass('tokenData');
    }

    /**
     *
     * @return \Gems\Util\TrackData
     */
    public function getTrackData()
    {
        return $this->_getClass('trackData');
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
        $address = IpFactory::parseAddressString($ip);
        if (! (($address instanceof AddressInterface) && strlen($ipRanges))) {
            return true;
        }
        $aType = $address->getAddressType();

        $ranges = explode('|', $ipRanges);
        foreach ($ranges as $range) {
            if (($sep = strpos($range, '-')) !== false) {
                $rangeIF = IpFactory::getRangeFromBoundaries(substr($range, 0, $sep), substr($range, $sep + 1));

            } else {
                $rangeIF = IpFactory::parseRangeString($range);
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
