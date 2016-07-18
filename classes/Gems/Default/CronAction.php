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
     * Should the batch be started automatically?
     * 
     * @var boolean
     */
    protected $_autoStart = true;
    
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
     * Perform automatic job, needs to be started by hand
     */
    public function batchAction()
    {
        $this->_autoStart = false;
        $this->indexAction();
    }

    /**
     * Perform automatic job mail
     */
    public function commJob()
    {
        $batch = $this->loader->getTaskRunnerBatch('cron');
        $batch->minimalStepDurationMs = 3000; // 3 seconds max before sending feedback
        if ($this->_autoStart) {
            $batch->autoStart = true;
        }

        if (!$batch->isLoaded()) {
            // Check for unprocessed tokens
            $tracker = $this->loader->getTracker();
            $tracker->processCompletedTokens(null, $this->currentUser->getUserId());
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