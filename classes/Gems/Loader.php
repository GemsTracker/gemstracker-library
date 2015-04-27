<?php

/**
 * Copyright (c) 2011, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Loader
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 * @package    Gems
 * @subpackage Loader
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Loader extends \Gems_Loader_LoaderAbstract
{
    /**
     *
     * @var \Gems_Agenda
     */
    protected $agenda;

    /**
     *
     * @var \Gems_Events
     */
    protected $events;

    /**
     *
     * @var \Gems_Export
     */
    protected $export;

    /**
     *
     * @var \Gems_Import_ImportLoader
     */
    protected $importLoader;

    /**
     *
     * @var \Gems_Model
     */
    protected $models;

    /**
     *
     * @var \Gems_Pdf
     */
    protected $pdf;

    /**
     *
     * @var \Gems_Export_RespondentExport
     */
    protected $respondentexport;

    /**
     *
     * @var \Gems_Roles
     */
    protected $roles;

    /**
     *
     * @var \Gems_Selector
     */
    protected $selector;

    /**
     *
     * @var \Gems_Snippets_SnippetLoader
     */
    protected $snippetLoader;

    /**
     *
     * @var \Gems_Tracker
     */
    protected $tracker;

    /**
     *
     * @var \Gems_Upgrades
     */
    protected $upgrades;

    /**
     *
     * @var \Gems_User_UserLoader
     */
    protected $userLoader;

    /**
     *
     * @var \Gems_Util
     */
    protected $util;

    /**
     *
     * @var \Gems_Versions
     */
    protected $versions;

    /**
     * Load project specific menu or general Gems menu otherwise
     *
     * @param GemsEscort $escort
     * @return \Gems_Menu
     */
    public function createMenu(GemsEscort $escort)
    {
        return $this->getMenu($escort);
        // return $this->_loadClass('Menu', true, func_get_args());
    }

    /**
     *
     * @return \Gems_Agenda
     */
    public function getAgenda()
    {
        return $this->_getClass('agenda');
    }

    /**
     *
     * @return \Gems_User_User
     */
    public function getCurrentUser()
    {
        $loader = $this->getUserLoader();

        return $loader->getCurrentUser();
    }

    /**
     *
     * @return \Gems_Events
     */
    public function getEvents()
    {
        return $this->_getClass('events');
    }

    /**
     *
     * @return \Gems_Export
     */
    public function getExport()
    {
        return $this->_getClass('export');
    }

    /**
     *
     * @return Gems_Export
     */
    public function getExportModelSource($exportModelSourceName)
    {
        return $this->_loadClass('Export_ModelSource_' . $exportModelSourceName, true);
    }

    /**
     *
     * @return Gems_Export
     */
    public function getTestExport()
    {
        return $this->_getClass('excelExport', 'Export_ExcelExport');
    }

    /**
     * @return \Gems_Mail
     */
    public function getMail($charset = null)
    {
        return $this->_loadClass('mail', true, array($charset));
    }

    /**
     * @return \Gems_Mail_MailLoader
     */
    public function getMailLoader()
    {
        return $this->_getClass('mailLoader', 'Mail_MailLoader');
    }

    public function getMailTargets()
    {
        $loader = $this->getMailLoader();
        return $loader->getMailTargets();
    }

    /**
     * Get the project specific menu or general Gems menu otherwise
     *
     * @param GemsEscort $escort
     * @return \Gems_Menu
     */
    public function getMenu(GemsEscort $escort)
    {
        return $this->_getClass('menu', 'Menu', func_get_args());
    }

    /**
     *
     * @return \Gems_Import_ImportLoader
     */
    public function getImportLoader()
    {
        return $this->_getClass('importLoader', 'Import_ImportLoader');
    }

    public function getMessenger()
    {
        return $this->_getClass('flashMessenger', 'Controller_Action_Helper_FlashMessenger');
    }

    /**
     *
     * @return \Gems_Model
     */
    public function getModels()
    {
        return $this->_getClass('models', 'model');
    }

    /**
     * Returns an organization object, initiated from the database.
     *
     * @param int $organizationId Optional, uses current user when empty
     * @return \Gems_User_Organization
     */
    public function getOrganization($organizationId = null)
    {
        $loader = $this->getUserLoader();

        return $loader->getOrganization($organizationId);
    }

    /**
     *
     * @return \Gems_Pdf
     */
    public function getPdf()
    {
        return $this->_getClass('pdf');
    }

    /**
     * Get a respondent object
     *
     * @param string $patientId   Patient number, you can use $respondentId instead
     * @param int $organizationId Organization id
     * @param int $respondentId   Optional respondent id, used when patient id is empty
     * @return \Gems_Tracker_Respondent
     */
    public function getRespondent($patientId, $organizationId, $respondentId = null)
    {
       return $this->_loadClass('Tracker_Respondent', true, array($patientId, $organizationId, $respondentId));
    }

    /**
     * Get a new respondentExport
     *
     * @return \Gems_Export_RespondentExport
     */
    public function getRespondentExport($container)
    {
        $this->addRegistryContainer($container, 'tmp_export');
        $class = $this->_loadClass('Export_RespondentExport', true);
        $this->removeRegistryContainer('tmp_export');

        return $class;
    }

    /**
     *
     * @param GemsEscort $escort
     * @return \Gems_Roles
     */
    public function getRoles(GemsEscort $escort)
    {
        return $this->_getClass('roles', null, array($escort));
    }

    /**
     *
     * @return \Gems_Selector
     */
    public function getSelector()
    {
        return $this->_getClass('selector');
    }

    /**
     *
     * @return \Gems_Snippets_SnippetLoader
     */
    public function getSnippetLoader($container)
    {
        $class = $this->_getClass('snippetLoader', 'Snippets_SnippetLoader');

        //now add the calling class as a container
        $class->getSource()->addRegistryContainer($container);
        return $class;
    }

    /**
     *
     * @param type $id
     * @param \MUtil_Batch_Stack_Stackinterface $stack Optional different stack than session stack
     * @return \Gems_Task_TaskRunnerBatch
     */
    public function getTaskRunnerBatch($id, \MUtil_Batch_Stack_Stackinterface $stack = null)
    {
        $id = preg_replace('/[^a-zA-Z0-9_]/', '', $id);

        if ((null == $stack) && isset($this->_containers[0]->cache)) {
            $cache = $this->_containers[0]->cache;

            // Make sure the cache is caching
            if (($cache instanceof \Zend_Cache_Core) && $cache->getOption('caching')) {
                $stack = new \MUtil_Batch_Stack_CacheStack($id, $this->_containers[0]->cache);
            }
        }
        $taskBatch = $this->_loadClass('Task_TaskRunnerBatch', true, array_filter(array($id, $stack)));

        if ($taskBatch instanceof \MUtil_Task_TaskBatch) {
            $taskBatch->setSource($this);
            $taskBatch->addTaskLoaderPrefixDirectories($this->_cascadedDirs($this->_dirs, 'Task'));
        }

        return $taskBatch;
    }

    /**
     *
     * @return \Gems_Tracker_TrackerInterface
     */
    public function getTracker()
    {
        return $this->_getClass('tracker');
    }

    /**
     *
     * @return \Gems_Upgrades
     */
    public function getUpgrades()
    {
        return $this->_getClass('upgrades');
    }

    /**
     *
     * @param string $login_name
     * @param int $organization
     * @return \Gems_User_User
     */
    public function getUser($login_name, $organization)
    {
        $loader = $this->getUserLoader();

        return $loader->getUser($login_name, $organization);
    }

    /**
     *
     * @return \Gems_User_UserLoader
     */
    public function getUserLoader()
    {
        return $this->_getClass('userLoader', 'User_UserLoader');
    }

    /**
     *
     * @return \Gems_Util
     */
    public function getUtil()
    {
        return $this->_getClass('util');
    }

    /**
     *
     * @return \Gems_Versions
     */
    public function getVersions()
    {
        return $this->_getClass('versions');
    }
}