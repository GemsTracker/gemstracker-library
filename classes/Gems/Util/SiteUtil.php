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
     * @var \MUtil_Registry_Source
     */
    protected $source;

    /**
     * @var \Gems_Util
     */
    protected $util;

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * This function is no needed if the classes are setup correctly
     *
     * @return void
     * /
    public function afterRegistry()
    {
        parent::afterRegistry();
        
        $this->loadUrlCache();
    } // */

    /**
     * @return \Gems\Util\SiteConsole A console only url
     */
    public function getConsoleUrl()
    {
        $site = new SiteConsole();
        $this->source->applySource($site);
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
     * @throws \Gems_Exception_Coding
     */
    public function getSiteForCurrentUrl($blockOnCreation = false)
    {
        if (\MUtil_Console::isConsole()) {
            $site = new SiteConsole('https://console', false);
            $this->source->applySource($site);
            return $site;
            
        } elseif (\Zend_Session::$_unitTestEnabled) {
            $url = 'https://test.example.site';
            
        } elseif (\Zend_Controller_Front::getInstance()->getResponse() instanceof \Zend_Controller_Request_Abstract) {
            // I found myself trying to do this so here we prefent this the hard way.
            throw new \Gems_Exception_Coding(
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

            // \MUtil_Echo::track(str_replace('?', "'$url'", $sql));
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
        $this->source->applySource($site);

        return $site;
    }

    /**
     * Returns the cron job lock
     *
     * @return \Gems_Util_LockFile
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
        return \MUtil_String::stripToHost($request->getServer(
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
     * @return bool
     */
    public function isRequestFromAllowedHost(\Zend_Controller_Request_Abstract $request)
    {
        if (\MUtil_Console::isConsole() || \Zend_Session::$_unitTestEnabled) {
            return true;
        }
        
        if (! $request instanceof \Zend_Controller_Request_Http) {
            // Should not really occur, but now the code knows the type
            return true;
        }
        
        if (! ($request->isPost() || $this->getSiteLock()->isLocked())) {
            // True when not a post and the site lock is unlocked
            return true; 
        }

        $incoming = $request->getServer('HTTP_ORIGIN',$request->getServer('HTTP_REFERER', false));
        if ($incoming) {
            // \MUtil_Echo::track($incoming);
            $site = $this->getSiteByFullUrl($incoming  . $request->getBasePath(), $request->isPost());
            if ($site) {
                if ($site->isBlocked()) {
                    return false;
                }
            } 
        }
        
        // Quick check without database access
        $host = $this->util->getCurrentURI();
        if ($host) {
            // \MUtil_Echo::track($host);
            $site = $this->getSiteByFullUrl($host, $request->isPost());
            if ($site) {
                return ! $site->isBlocked();
            }
        }
        
        return false;
    }
}