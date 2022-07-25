<?php

/**
 *
 * @package    Gems
 * @subpackage Loader
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems;

/**
 *
 * @package    Gems
 * @subpackage Loader
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Loader extends \Gems\Loader\LoaderAbstract
{
    /**
     *
     * @var array of arrays of \Gems\Tracker\Respondent
     */
    private $_respondents = array();

    /**
     *
     * @var \Gems\Agenda
     */
    protected $agenda;

    /**
     *
     * @var \Gems\Events
     */
    protected $events;

    /**
     *
     * @var \Gems\Export
     */
    protected $export;

    /**
     *
     * @var \Gems\Import\ImportLoader
     */
    protected $importLoader;

    /**
     * Required
     *
     * @var \Gems\Menu
     */
    protected $menu;

    /**
     *
     * @var \Gems\Model
     */
    protected $models;

    /**
     *
     * @var \Gems\Pdf
     */
    protected $pdf;

    /**
     *
     * @var \Gems\Export\RespondentExport
     */
    protected $respondentexport;

    /**
     *
     * @var \Gems\Roles
     */
    protected $roles;

    /**
     *
     * @var \Gems\Selector
     */
    protected $selector;

    /**
     *
     * @var \Gems\Snippets\SnippetLoader
     */
    protected $snippetLoader;

    /**
     *
     * @var \Gems\Tracker
     */
    protected $tracker;

    /**
     *
     * @var \Gems\Upgrades
     */
    protected $upgrades;

    /**
     *
     * @var \Gems\User\UserLoader
     */
    protected $userLoader;

    /**
     *
     * @var \Gems\Util
     */
    protected $util;

    /**
     *
     * @var \Gems\Versions
     */
    protected $versions;

    /**
     * Load project specific menu or general \Gems menu otherwise
     *
     * @param \Gems\Escort $escort
     * @return \Gems\Menu
     */
    public function createMenu(\Gems\Escort $escort)
    {
        return $this->_getClass('menu', 'Menu', func_get_args());
    }

    /**
     *
     * @return \Gems\Agenda
     */
    public function getAgenda()
    {
        return $this->_getClass('agenda');
    }

    /**
     *
     * @return \Gems\Communication\CommunicationLoader
     */
    public function getCommunicationLoader()
    {
        return $this->_getClass('communicationLoader', 'Communication\\CommunicationLoader');
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
     * @return \Gems\User\User
     */
    public function getCurrentUser()
    {
        $loader = $this->getUserLoader();

        return $loader->getCurrentUser();
    }

    /**
     * @return \Gems\Db\DbTranslations
     */
    public function getDbTranslations($config=null)
    {
        return $this->_loadClass('Db\\DbTranslations', true, ['config' => $config]);
    }

    /**
     *
     * @param int $userId
     * @param \Zend_Db_Adapter_Abstract $db
     * @return \Gems\User\Embed\EmbedLoader
     */
    public function getEmbedDataObject($userId, \Zend_Db_Adapter_Abstract $db)
    {
        return $this->_loadClass('User\\Embed\\EmbeddedUserData', true, [$userId, $db, $this]);
    }

    /**
     * @return \Gems\User\Embed\EmbedLoader
     */
    public function getEmbedLoader()
    {
        return $this->_getClass('User\\Embed\\EmbedLoader');
    }

    /**
     *
     * @return \Gems\Events
     */
    public function getEvents()
    {
        return $this->_getClass('events');
    }

    /**
     *
     * @return \Gems\Export
     */
    public function getExport()
    {
        return $this->_getClass('export');
    }

    /**
     *
     * @return \Gems\Export\ModelSource\ExportModelSourceAbstract
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
     * @param \MUtil\Model\ModelAbstract $model
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
     * @return \Gems\Import\ImportLoader
     */
    public function getImportLoader()
    {
        return $this->_getClass('importLoader', 'Import\\ImportLoader');
    }

    /**
     * @return \Gems\Mail
     */
    public function getMail($charset = null)
    {
        return $this->_loadClass('mail', true, array($charset));
    }

    /**
     * @return \Gems\Mail\MailLoader
     */
    public function getMailLoader()
    {
        return $this->_getClass('mailLoader', 'Mail\\MailLoader');
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
     * Get the project specific menu or general \Gems menu otherwise
     *
     * @return \Gems\Menu
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
        return $this->_getClass('flashMessenger', 'Controller\\Action\\Helper\\FlashMessenger');
    }

    /**
     *
     * @return \Gems\Model
     */
    public function getModels()
    {
        return $this->_getClass('models', 'model');
    }

    /**
     * Returns an organization object, initiated from the database.
     *
     * @param int $organizationId Optional, uses current user when empty
     * @return \Gems\User\Organization
     */
    public function getOrganization($organizationId = null)
    {
        $loader = $this->getUserLoader();

        return $loader->getOrganization($organizationId);
    }

    /**
     *
     * @return \Gems\Pdf
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
     * @return \Gems\Tracker\Respondent
     */
    public function getRespondent($patientId, $organizationId, $respondentId = null)
    {
        if ($patientId) {
            if (isset($this->_respondents[$organizationId][$patientId])) {
                return $this->_respondents[$organizationId][$patientId];
            }
        }
        $newResp = $this->_loadClass('Tracker\\Respondent', true, array($patientId, $organizationId, $respondentId));
        $patientId = $newResp->getPatientNumber();

        if (! isset($this->_respondents[$organizationId][$patientId])) {
            $this->_respondents[$organizationId][$patientId] = $newResp;
        }

        return $this->_respondents[$organizationId][$patientId];
    }

    /**
     * Get a new respondentExport
     *
     * @return \Gems\Export\RespondentExport
     */
    public function getRespondentExport()
    {
        $class = $this->_loadClass('Export\\RespondentExport', true);

        return $class;
    }

    /**
     *
     * @param \Gems\Escort $escort
     * @return \Gems\Roles
     */
    public function getRoles(\Gems\Escort $escort)
    {
        return $this->_getClass('roles', null, array($escort, $escort->logger));
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
     * @return \Gems\Selector
     */
    public function getSelector()
    {
        return $this->_getClass('selector');
    }

    /**
     *
     * @return \Gems\Snippets\SnippetLoader
     */
    public function getSnippetLoader($container)
    {
        $class = $this->_getClass('snippetLoader', 'Snippets\\SnippetLoader');

        //now add the calling class as a container
        $class->getSource()->addRegistryContainer($container);
        return $class;
    }

    /**
     *
     * @return \Gems_SubscribeThrottleValidator
     */
    public function getSubscriptionThrottleValidator()
    {
        return $this->_loadClass('Validate\\SubscriptionThrottleValidator', true);
    }

    /**
     *
     * @param string $id
     * @param \MUtil\Batch\Stack\Stackinterface $stack Optional different stack than session stack
     * @return \Gems\Task\TaskRunnerBatch
     */
    public function getTaskRunnerBatch($id, \MUtil\Batch\Stack\Stackinterface $stack = null)
    {
        $id = preg_replace('/[^a-zA-Z0-9_]/', '', $id);

        if ((null == $stack) && isset($this->_containers[0]->cache)) {
            $cache = $this->_containers[0]->cache;

            // Make sure the cache is caching
            if (($cache instanceof \Zend_Cache_Core) && $cache->getOption('caching')) {
                $stack = new \MUtil\Batch\Stack\CacheStack($id, $this->_containers[0]->cache);
            }
        }
        $taskBatch = $this->_loadClass('Task\\TaskRunnerBatch', true, array_filter(array($id, $stack)));

        if ($taskBatch instanceof \MUtil\Task\TaskBatch) {
            $taskBatch->setSource($this);
            $taskBatch->addTaskLoaderPrefixDirectories($this->_cascadedDirs($this->_dirs, 'Task'));
        }

        return $taskBatch;
    }

    /**
     *
     * @return \Gems\Tracker\TrackerInterface
     */
    public function getTracker()
    {
        return $this->_getClass('tracker');
    }

    /**
     *
     * @return \Gems\Upgrades
     */
    public function getUpgrades()
    {
        return $this->_getClass('upgrades');
    }

    /**
     *
     * @param string $login_name
     * @param int $organization
     * @return \Gems\User\User
     */
    public function getUser($login_name, $organization)
    {
        $loader = $this->getUserLoader();

        return $loader->getUser($login_name, $organization);
    }

    /**
     *
     * @return \Gems\User\UserLoader
     */
    public function getUserLoader()
    {
        return $this->_getClass('userLoader', 'User\\UserLoader');
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
     * @return \Gems\Util
     */
    public function getUtil()
    {
        return $this->_getClass('util');
    }

    /**
     *
     * @return \Gems\Versions
     */
    public function getVersions()
    {
        return $this->_getClass('versions');
    }
}
