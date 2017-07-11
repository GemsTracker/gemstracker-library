<?php

/**
 *
 * @author     Michiel Rook <michiel@touchdownconsulting.nl>
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Performs bulk-mail action, can be called from a cronjob
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class Gems_Default_CronAction extends \Gems_Controller_Action
{
    /**
     *
     * @var \Gems_AccessLog
     */
    public $accesslog;

    /**
     *
     * @var \Gems_User_User
     */
    public $currentUser;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;

    /**
     *
     * @var GemsEscort
     */
    public $escort;

    /**
     *
     * @var \Gems_Loader
     */
    public $loader;

    /**
     *
     * @var \Gems_Menu
     */
    public $menu;

    /**
     * Set to true in child class for automatic creation of $this->html.
     *
     * Otherwise call $this->initHtml()
     *
     * @var boolean $useHtmlView
     */
    public $useHtmlView = true;

    /**
     *
     * @var \Gems_Util
     */
    public $util;

    /**
     * Perform automatic job mail
     */
    public function commJob()
    {
        $batch = $this->loader->getTaskRunnerBatch('cron');
        $batch->setMessageLogFile(GEMS_ROOT_DIR . '/var/logs/cron-job.log');
        $batch->minimalStepDurationMs = 3000; // 3 seconds max before sending feedback
        $batch->autoStart = true;

        if (!$batch->isLoaded()) {
            // Check for unprocessed tokens
            $tracker = $this->loader->getTracker();
            $tracker->processCompletedTokens(null, $this->currentUser->getUserId());
            $batch->addMessage($this->_("Starting mail jobs"));
            $batch->addTask('Mail\\AddAllMailJobsTask');
        }

        $title = $this->_('Executing cron jobs');
        $this->_helper->BatchRunner($batch, $title, $this->accesslog);

        $this->html->br();
    }

    /**
     * Action that switches the cron job lock on or off.
     */
    public function cronLockAction()
    {
        // Switch lock
        $this->util->getCronJobLock()->reverse();

        // Redirect
        $request = $this->getRequest();
        $this->_reroute($this->menu->getCurrentParent()->toRouteUrl());
    }

    /**
     * Returns the login name belonging to this user.
     *
     * @param int $userId
     * @return string
     */
    protected function getUserLogin($userId)
    {
        return $this->db->fetchOne("SELECT gsf_login FROM gems__staff WHERE gsf_id_user = ?", $userId);
    }

    /**
     * Returns the Email belonging to this user.
     *
     * @param int $userId
     * @return string
     */
    protected function getUserEmail($userId)
    {
        return $this->db->fetchOne("SELECT gsf_email FROM gems__staff WHERE gsf_id_user = ?", $userId);
    }

    /**
     * The general "do the jobs" action
     */
    public function indexAction()
    {
        $this->initHtml();
        if ($this->util->getMaintenanceLock()->isLocked()) {
            $this->addMessage($this->_('Cannot run cron job in maintenance mode.'));
        } elseif ($this->util->getCronJobLock()->isLocked()) {
            $this->addMessage($this->_('Cron jobs turned off.'));
        } else {
            $this->commJob();
        }
        $this->util->getMonitor()->startCronMailMonitor();
    }

    /**
     * Check job monitors
     */
    public function monitorAction()
    {
        $this->addMessage($this->util->getMonitor()->checkMonitors());
    }
}