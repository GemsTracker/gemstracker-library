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
    /**
     * @var \Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     * @inheritDoc
     */
    protected function loadData($id)
    {
        $namedOrgs = $this->util->getDbLookup()->getOrganizationsForLogin();
        if (! $namedOrgs) {
            $namedOrgs = [0 => 'create db first'];
        }
        
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
            'orgs'                     => $namedOrgs,
        ];
    }

}