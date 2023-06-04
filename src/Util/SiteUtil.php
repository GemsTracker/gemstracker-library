<?php

/**
 *
 * @package    Gems
 * @subpackage Util
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2021, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Util;

use Gems\Snippets\Ask\RedirectUntilGoodbyeSnippet;
use Zalt\Loader\ProjectOverloader;

/**
 *
 * @package    Gems
 * @subpackage Util
 * @license    New BSD License
 * @since      Class available since version 1.9.1
 */
class SiteUtil extends UtilAbstract
{
    CONST ORG_SEPARATOR = '|';

    /**
     * @var ProjectOverloader
     */
    protected $overLoader;

    /**
     * @var \Gems\Util
     */
    protected $util;

    /**
     * @param $host
     * @param $basePath Optional addiitonal basepath
     * @return string Normalized https:// string for a host name
     */
    protected function _hostToUrl($host, $basePath)
    {
        return (\MUtil\Https::on() ? 'https' : 'http') . '://' . $host . $basePath;
    }

    /**
     * @return \Gems\Util\SiteConsole A console only url
     */
    public function getConsoleUrl()
    {
        $site = new SiteConsole();
        $this->overLoader->applyToLegacyTarget($site);
        return $site;
    }

    /**
     * Get the first url for all organizations
     *
     * @return \Gems\Util\SiteUrl|null
     */
    public function getOneForAll()
    {
        try {
            $sql = "SELECT gsi_url FROM gems__sites 
                        WHERE gsi_select_organizations = 0 AND gsi_active = 1 AND gsi_blocked = 0 
                        ORDER BY gsi_order, gsi_id";

            $url = $this->db->fetchOne($sql);

            if ($url) {
                return $this->getSiteForUrl($url);
            }

        } catch (\Zend_Db_Statement_Exception $exc) {
            // Intentional fall through
        }
        return null;
    }

    /**
     * @param $orgId
     * @return string Preferred url for organization
     */
    public function getOrganizationPreferredUrl($orgId)
    {
        try {
            $id = self::ORG_SEPARATOR . intval($orgId) . self::ORG_SEPARATOR;
            $sql = "SELECT gsi_url FROM gems__sites 
                        WHERE gsi_select_organizations = 0 OR gsi_organizations LIKE '%$orgId%' AND gsi_active = 1 AND gsi_blocked = 0
                        ORDER BY gsi_order, gsi_id";

            return $this->db->fetchOne($sql);
        } catch (\Zend_Db_Statement_Exception $exc) {
            // The old return value
            return $this->util->getCurrentURI();
        }
    }

    /**
     * @param false $blockOnCreation
     * @return \Gems\Util\SiteUrl
     * @throws \Gems\Exception\Coding
     */
    public function getSiteForCurrentUrl($blockOnCreation = false)
    {
        if (\MUtil\Console::isConsole()) {
            return $this->getConsoleUrl();
        } elseif (\Zend_Session::$_unitTestEnabled) {
            $url = 'https://test.example.site';
            $blockOnCreation = false;

        } elseif (\Zend_Controller_Front::getInstance()->getResponse() instanceof \Zend_Controller_Request_Abstract) {
            // I found myself trying to do this so here we prefent this the hard way.
            throw new \Gems\Exception\Coding(
                __CLASS__ . '->' . __FUNCTION__ . "() cannot be called before the request object is initialized."
            );

        } else {
            $url = $this->util->getCurrentURI();

        }

        return $this->getSiteForUrl($url, $blockOnCreation);
    }

    /**
     * @param string $url A complete url (not just the server) or otherwise the current url is used
     * @param boolean $blockOnCreation
     * @return \Gems\Util\SiteUrl
     */
    public function getSiteByFullUrl($url, $blockOnCreation = true)
    {
        try {
            $sql = "SELECT gsi_url FROM gems__sites 
                        WHERE ? LIKE CONCAT(gsi_url, '%') 
                        ORDER BY gsi_order, gsi_id";

            // \MUtil\EchoOut\EchoOut::track(str_replace('?', "'$url'", $sql));
            $foundUrl = $this->db->fetchOne($sql, $url);

            if ($foundUrl) {
                return $this->getSiteForUrl($foundUrl);
            }

            return $this->getSiteForUrl($url, $blockOnCreation);

        } catch (\Zend_Db_Statement_Exception $exc) {
            return null;
        }

    }

    /**
     * @param string $url An url or otherwise the current url is used
     * @param boolean $blockOnCreation
     * @return \Gems\Util\SiteUrl
     */
    public function getSiteForUrl($url, $blockOnCreation = false)
    {
        $site = new SiteUrl($url, $blockOnCreation);
        $this->overLoader->applyToLegacyTarget($site);

        return $site;
    }

    /**
     * Returns the cron job lock
     *
     * @return \Gems\Util\LockFile
     */
    public function getSiteLock()
    {
        return $this->util->getLockFile('site_lock.txt');
    }

    /**
     * @param \Zend_Controller_Request_Abstract $request
     * @return string
     */
    public function getUsedHost(\Zend_Controller_Request_Abstract $request)
    {
        return \MUtil\StringUtil\StringUtil::stripToHost($request->getServer(
            'HTTP_ORIGIN',
            $request->getServer(
                'HTTP_REFERER',
                $this->util->getCurrentURI())));
    }

    /**
     * Get the organizations not served by a specific site
     *
     * @return array [$orgId]
     */
    public function getUnspecificOrganizations()
    {
        $existingOrganizations = array_keys($this->util->getDbLookup()->getOrganizations());

        try {
            $sql = "SELECT gsi_organizations FROM gems__sites WHERE gsi_select_organizations = 1";

            $organizationStrings = $this->db->fetchCol($sql);

            if ($organizationStrings) {
                $servedOrganizations = array_unique(array_filter(explode(self::ORG_SEPARATOR, implode('', $organizationStrings))));

                return array_diff($existingOrganizations, $servedOrganizations);
            }
        } catch (\Zend_Db_Statement_Exception $exc) {
            // Intentional fall through
        }
        return $existingOrganizations;
    }

    /**
     * @param \Zend_Controller_Request_Abstract $request
     * @return string The not allowed host
     */
    public function isRequestFromAllowedHost(\Zend_Controller_Request_Abstract $request)
    {
        if (\MUtil\Console::isConsole() || \Zend_Session::$_unitTestEnabled) {
            return null;
        }

        if (! $request instanceof \Zend_Controller_Request_Http) {
            // Should not really occur, but now the code knows the type
            return null;
        }

        $basePath = $request->getBasePath();
        $isPost   = $request->isPost();
        $locked   = $this->getSiteLock()->isLocked();

        $hosts = [];
        if (isset($_SERVER['HTTP_HOST'])) {
            $hosts[] = $_SERVER['HTTP_HOST'];
        }
        if (isset($_SERVER['SERVER_NAME'])) {
            $hosts[] = $_SERVER['SERVER_NAME'];
        }
        // $hosts[] = 'www.evilsite.com';
        // \MUtil\EchoOut\EchoOut::track($hosts);
        foreach (array_unique($hosts) as $host) {
            $url  = $this->_hostToUrl($host, $basePath);
            $site = $this->getSiteForUrl($url, $isPost);
            if ($site) {
                if ($site->isNew()) {
                    if ($isPost || $locked) {
                        return $host;
                    }
                } elseif ($site->isBlocked()) {
                    return $host;
                }
            }
        }

        if ($isPost) {
            $referrers = [];
            $referrers[] = $request->getServer('HTTP_ORIGIN');
            $referrers[] = $request->getServer('HTTP_REFERER');
            // $referrers[] = 'http://www.evilsite.com/';
            // $referrers[] = 'http://www.evilsite.com/pulse/id/1?attack=mode';
            // $referrers[] = 'http://www.evilsite2.com/';
            //\MUtil\EchoOut\EchoOut::track($referrers);
            foreach (array_unique(array_filter($referrers)) as $referrer) {
                if (!empty($basePath) && !\MUtil\StringUtil\StringUtil::contains($referrer, $basePath)) {
                    $referrer = rtrim($referrer, '/') . $basePath;
                }
                $site = $this->getSiteByFullUrl($referrer, $isPost);
                if ($site) {
                    if ($site->isNew()) {
                        if ($isPost) {
                            return \MUtil\StringUtil\StringUtil::beforeChars($referrer, '?&<>=');
                        }
                    } elseif ($site->isBlocked()) {
                        return $site->getUrl();
                    }
                }
            }
        }

        return null;
    }
}
