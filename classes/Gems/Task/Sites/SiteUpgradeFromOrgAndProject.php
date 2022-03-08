<?php

/**
 *
 * @package    Gems
 * @subpackage Task\Sites
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2021, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Task\Sites;

/**
 *
 * @package    Gems
 * @subpackage Task\Sites
 * @license    New BSD License
 * @since      Class available since version 1.9.1
 */
class SiteUpgradeFromOrgAndProject extends \MUtil_Task_TaskAbstract
{
    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;
    
    /**
     * @var \Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     * @var \Gems_Util
     */
    protected $util;

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $batch = $this->getBatch();
        $orgs  = $this->db->fetchPairs(
            "SELECT gor_id_organization, gor_url_base FROM gems__organizations 
                    WHERE gor_url_base IS NOT NULL AND gor_url_base != ''");

        $addHttp = ! $this->project->isHttpsRequired();
        if (isset($project['console']['url'])) {
            $batch->addTask('Sites\\AddToBaseUrl', 'https://' . $project['console']['url']);
            if ($addHttp) {
                $batch->addTask('Sites\\AddToBaseUrl', 'http://' . $project['console']['url']);
            }
        }

        $batch->addTask('Sites\\AddToBaseUrl', $this->util->getCurrentURI());
        
        foreach ($orgs as $id => $baseUrls) {
            foreach (explode(' ', $baseUrls) as $url) {
                $batch->addTask('Sites\\AddToBaseUrl', $url, $id);
            }
        }

        $project = $this->project;
        if (isset($project['allowedSourceHosts'])) {
            foreach ((array) $project['allowedSourceHosts'] as $host) {
                $batch->addTask('Sites\\AddToBaseUrl', "https://$host");
                
                if ($addHttp) {
                    $batch->addTask('Sites\\AddToBaseUrl', "http://$host");
                }
            }
        }

        $batch->addTask('Sites\\BlockNewSites');
    }
}
