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
    /**
     * @var array url => SiteUrl object
     */
    protected $_sites;

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
     * @param $orgId
     * @return string Preferred url for organization
     */
    public function getOrganizationPreferredUrl($orgId)
    {
        try {
            $id = intval($orgId);
            $sql = "SELECT gsi_url FROM gems__sites WHERE gsi_select_organizations = 0 OR gsi_organizations LIKE '%|$id|%' ORDER BY gsi_order, gsi_id";

            return $this->db->fetchOne($sql);
        } catch (\Zend_Db_Statement_Exception $exc) {
            // The old return value
            return $this->util->getCurrentURI();
        }
    }

    /**
     * @param false $blockOnCreation
     * @return \Gems\Util\SiteUrl
     */
    public function getSiteForCurrentUrl($blockOnCreation = false)
    {
        return $this->getSiteForUrl($this->util->getCurrentURI(), $blockOnCreation);
    }
    
    /**
     * @param string $url An url or otherwise the current url is used
     * @param false $blockOnCreation
     * @return \Gems\Util\SiteUrl
     */
    public function getSiteForUrl($url, $blockOnCreation = false)
    {
        if (\MUtil_Console::isConsole() || \Zend_Session::$_unitTestEnabled) {
            $this->_sites[$url] = new SiteConsole($url, $blockOnCreation);
            $this->source->applySource($this->_sites[$url]);
        }        
        
        if (! isset($this->_sites[$url])) {
            $this->_sites[$url] = new SiteUrl($url, $blockOnCreation);
            $this->source->applySource($this->_sites[$url]);
        }

        return $this->_sites[$url];
    }

    /**
     * @param \Zend_Controller_Request_Abstract $request
     * @return bool
     */
    public function isPostFromAllowedHost(\Zend_Controller_Request_Abstract $request)
    {
        if (\MUtil_Console::isConsole() || \Zend_Session::$_unitTestEnabled) {
            return true;
        }
        
        if (! $request instanceof \Zend_Controller_Request_Http) {
            // Should not really occur, but now the code knows the type
            return true;
        }
        
        if (! $request->isPost()) {
            // True when not a post'
            return true; 
        }

        $incoming = $request->getServer('HTTP_ORIGIN', $request->getServer('HTTP_REFERER', false));
        if (! $incoming) {
            // Nothing to check against
            return true;
        }
        
        $host = \MUtil_String::stripToHost($incoming);
        if ($host == \MUtil_String::stripToHost($request->getServer('HTTP_HOST'))) {
            return true;
        }
        
        // TODO: check the db
        
        return false;
    }
}