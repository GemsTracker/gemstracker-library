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
 * The "SiteUrl" to use when running on the console
 * @package    Gems
 * @subpackage Util
 * @license    New BSD License
 * @since      Class available since version 1.9.1
 */
class SiteConsole extends SiteUrl
{
    const CONSOLE_URL = 'https://console';
    /**
     * @var \Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     * Creates the object.
     */
    public function __construct()
    {
        parent::__construct(self::CONSOLE_URL, false);
    }
    
    /**
     * @return boolean Is this organization id allowed for this site
     */
    public function hasUrlOrganizationsId($orgId)
    {
        return true;
    }
    
    /**
     * @inheritDoc
     */
    protected function loadData($id)
    {
        return [
            'gsi_url'                  => $id,
            'gsi_order'                => 1000,
            'gsi_select_organizations' => 0,
            'gsi_organizations'        => [],
            'gsi_style'                => 'gems',
            'gsi_style_fixed'          => '0',
            'gsi_iso_lang'             => $this->project->getLocaleDefault(),
            'gsi_active'               => 1,
            'gsi_blocked'              => 0,
            'orgs'                     => $this->util->getDbLookup()->getOrganizationsForLogin(),
        ];
    }

}