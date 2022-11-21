<?php

/**
 *
 * @author     Michiel Rook <michiel@touchdownconsulting.nl>
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Actions;

/**
 * Performs bulk-mail action, can be called from a cronjob
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class CronAction extends \Gems\Controller\Action
{
    /**
     *
     * @var \Gems\AccessLog
     */
    public $accesslog;

    /**
     *
     * @var \Gems\Loader
     */
    public $loader;

    /**
     *
     * @var \Gems\Menu
     */
    public $menu;

    /**
     * Set to true in child class for automatic creation of $this->html.
     *
     * Otherwise call $this->initHtml()
     *
     * @var boolean $useHtmlView
     */
    public bool $useHtmlView = true;

    /**
     *
     * @var \Gems\Util
     */
    public $util;

    /**
     * Perform automatic job mail
     */
    public function commJob()
    {
        $batch = $this->loader->getMailLoader()->getCronBatch();
        $batch->autoStart = true;

        $this->_helper->BatchRunner($batch, $this->_('Executing all cron jobs'), $this->accesslog);

        // $this->html->br();
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
     * Check all monitors
     */
    public function monitorAction()
    {
        $this->addMessage($this->util->getMonitor()->checkMonitors());
    }
}