<?php

/**
 *
 * @package    Gems
 * @subpackage Loader
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
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
     * @var array of arrays of \Gems_Tracker_Respondent
     */
    private $_respondents = array();

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
     * Required
     *
     * @var \Gems_Menu
     */
    protected $menu;

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
     * @param \GemsEscort $escort
     * @return \Gems_Menu
     */
    public function createMenu(\GemsEscort $escort)
    {
        return $this->_getClass('menu', 'Menu', func_get_args());
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
     * @return \Gems\Conditions
     */
    public function getConditions()
    {
        return $this->_getClass('conditions');
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
     * @return \Gems_Export_ModelSource_ExportModelSourceAbstract
     */
    public function getExportModelSource($exportModelSourceName)
    {
        return $this->_loadClass('Export_ModelSource_' . $exportModelSourceName, true);
    }

    /**
     * Returns an instance (row) of the the model, this allows for easy loading of instances
     *
     * Best usage would be to load from within the model, so return type can be
     * fixed there and code completion would work like desired
     *
     * @param string $name
     * @param \MUtil_Model_ModelAbstract $model
     * @param array $data
     * @return mixed
     */
    public function getInstance($name, $model, $data)
    {
        $instance = $this->_loadClass($name, true, array($model, $data));

        return $instance;
    }

    /**
     *
     * @return \Gems_Import_ImportLoader
     */
    public function getImportLoader()
    {
        return $this->_getClass('importLoader', 'Import_ImportLoader');
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

    /**
     * Get the possible mail targets
     *
     * @return Array  mail targets
     */
    public function getMailTargets()
    {
        return $this->getMailLoader()->getMailTargets();
    }

    /**
     * Get the project specific menu or general Gems menu otherwise
     *
     * @return \Gems_Menu
     */
    public function getMenu()
    {
        return $this->menu;
    }

    /**
     *
     * @return \Zend_Controller_Action_Helper_FlashMessenger
     */
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
        if ($patientId) {
            if (isset($this->_respondents[$organizationId][$patientId])) {
                return $this->_respondents[$organizationId][$patientId];
            }
        }
        $newResp = $this->_loadClass('Tracker_Respondent', true, array($patientId, $organizationId, $respondentId));
        $patientId = $newResp->getPatientNumber();

        if (! isset($this->_respondents[$organizationId][$patientId])) {
            $this->_respondents[$organizationId][$patientId] = $newResp;
        }

        return $this->_respondents[$organizationId][$patientId];
    }

    /**
     * Get a new respondentExport
     *
     * @return \Gems_Export_RespondentExport
     */
    public function getRespondentExport()
    {
        $class = $this->_loadClass('Export_RespondentExport', true);

        return $class;
    }

    /**
     *
     * @param GemsEscort $escort
     * @return \Gems_Roles
     */
    public function getRoles(\GemsEscort $escort)
    {
        return $this->_getClass('roles', null, array($escort));
    }

    /**
     *
     * @return \Gems\Screens\ScreenLoader
     */
    public function getScreenLoader()
    {
        return $this->_getClass('screenLoader', 'Screens\ScreenLoader');
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
     * @param string $id
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
     * @return \Gems\User\Mask\MaskStore
     */
    public function getUserMaskStore()
    {
        return $this->_getClass('maskStore', 'User\\Mask\\MaskStore');
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