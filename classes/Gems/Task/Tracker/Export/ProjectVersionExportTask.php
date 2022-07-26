<?php

/**
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Task\Tracker\Export;

/**
 *
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @copyright  Copyright (c) 2016 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Mar 22, 2016 1:57:25 PM
 */
class ProjectVersionExportTask  extends TrackExportAbstract
{
    /**
     * @var array
     */
    protected $config;

    /**
     *
     * @var \Gems\Util
     */
    protected $util;

    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     */
    public function execute($trackId = null, $exportOrganizations = false)
    {
        $versions = $this->loader->getVersions();

        $data = [
            'gems_version'    => $versions->getGemsVersion(),
            'project'         => null,
            'project_env'     => APPLICATION_ENV,
            'project_url'     => $this->util->getCurrentURI(), 
            'project_version' => $versions->getProjectVersion(),
        ];

        if (isset($this->config['app']['name'])) {
            $data['project'] = $this->config['app']['name'];
        }


        // Main version data
        $this->exportTypeHeader('version', false);
        $this->exportFieldHeaders($data);
        $this->exportFieldData($data);
        $this->exportFlush();
    }
}
