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

/**
 *
 * @package    Gems
 * @subpackage Util
 * @license    New BSD License
 * @since      Class available since version 1.9.1
 */
class SiteUrl extends \Gems_Registry_CachedArrayTargetAbstract
{
    /**
     * @var bool When true this site is automatically blocked when created
     */
    protected $_blockOnCreation = false;
    
    /**
     * Variable to add tags to the cache for cleanup.
     *
     * @var array
     */
    protected $_cacheTags = ['urlsites', 'organization', 'organizations'];

    /**
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     * @var \Gems_Log
     */
    protected $logger;

    /**
     * @var \Gems_Util
     */
    protected $util;
    
    /**
     * Creates the object.
     *
     * @param mixed $id Whatever identifies this object.
     * @param false $blockOnCreation
     */
    public function __construct($id, $blockOnCreation = false)
    {
        parent::__construct($id);
        
        $this->_blockOnCreation = $blockOnCreation;
    }

    /**
     * @return string The url itself
     */
    public function getUrl()
    {
        return $this->_data['gsi_url'];
    }

    /**
     * @return array orgId => name
     */
    public function getUrlOrganizations()
    {
        return $this->_data['orgs'];
    }

    /**
     * @return boolean Is  this organization id allowed for this site 
     */
    public function hasUrlOrganizationsId($orgId)
    {
        return array_key_exists($orgId, $this->_data['orgs']);
    }

    /**
     * @return bool Is the site blocked as input source 
     */
    public function isBlocked()
    {
        return 1 == $this->_data['gsi_blocked'];
    }
    
    /**
     * @inheritDoc
     */
    protected function loadData($id)
    {
        try {
            $model = $this->loader->getModels()->getSiteModel();

            $data = $model->loadFirst(['gsi_url' => $id]);

            if (! $data) {
                // Auto insert the site
                $data = $model->save([
                    'gsi_url'     => $id,
                    'gsi_blocked' => ($this->_blockOnCreation ? 1 : 0),
                    ]);
            }
        } catch (\Zend_Db_Adapter_Exception $e) {
            // In case the table does not exist, create temporary data
            $data = [
                'gsi_url'                  => $id,
                'gsi_order'                => 10,
                'gsi_select_organizations' => 0,
                'gsi_organizations'        => [],
                'gsi_style'                => 'gems',
                'gsi_style_fixed'          => '0',
                'gsi_iso_lang'             => 'en',
                'gsi_active'               => 1,
                'gsi_blocked'              => ($this->_blockOnCreation ? 1 : 0),
            ];    
            
            $this->logger->logError($e);
            // \MUtil_Echo::track($e->getMessage());
        }
        
        $namedOrgs = $this->util->getDbLookup()->getOrganizationsForLogin();
        if ($data['gsi_select_organizations']) {
            foreach ($data['gsi_organizations'] as $orgId) {
                if (isset( $namedOrgs[$orgId])) {
                    $data['orgs'][$orgId] = $namedOrgs[$orgId];
                }
            }
        } else {
            $data['orgs'] = $namedOrgs;
        } 
        
        return $data;
    }
}