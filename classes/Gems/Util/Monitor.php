<?php

/**
 * Copyright (c) 2015, Erasmus MC
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
 * DISCLAIMED. IN NO EVENT SHALL MAGNAFACTA BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Util
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: Monitor.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

namespace Gems\Util;

use \MUtil\Util\MonitorJob;

/**
 *
 *
 * @package    Gems
 * @subpackage Util
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Mar 2, 2016 1:42:12 PM
 */
class Monitor extends UtilAbstract
{
    /**
     *
     * @var \Zend_Locale
     */
    protected $locale;

    /**
     *
     * @var \Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     *
     * @var \Gems_Util
     */
    protected $util;

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        MonitorJob::$monitorDateFormat  = 'l j F Y H:i';
        MonitorJob::$monitorDir         = GEMS_ROOT_DIR . '/var/settings';
    }

    /**
     * Performs a check for all set monitors
     *
     * @return array of messages
     */
    public function checkMonitors()
    {
        return MonitorJob::checkJobs();
    }

    /**
     * Start the cron mail monitor
     *
     * @return boolean True when the job was started
     */
    public function reverseMaintenanceMonitor()
    {
        $job  = new MonitorJob($this->project->getName() . ' maintenance mode');
        $lock = $this->util->getMaintenanceLock();

        if ($lock->isLocked()) {
            $job->stop();
            $lock->unlock();
            return;
        }

        $lock->lock();

        $to = $this->project->getMonitorTo('maintenancemode');

        if (! $to) {
            return false;
        }

        switch ($this->project->getLocaleDefault()) {
            case 'nl':
                $subject = "{name} staat al meer dan {periodHours} uur aan";
                $messageBbText = "L.S.,

De [b]{name}[/b] is op {setTime} aangezet en staat nog steeds aan.

Dit is waarschuwing nummer [b]{mailCount}[/b]. Controleer s.v.p. of de onderhouds modus nog steeds nodig is.

Dit is een automatische waarschuwing.";
                break;

            default:
                $subject = "{name} has been active for over {periodHours} hours";
                $messageBbText = "L.S.,

The [b]{name}[/b] was activated at {setTime} and is still active.

This is notice number {mailCount}. Please check whether the maintenance mode is still required.

This messages was send automatically.";
                break;

        }

        $job->setFrom($this->project->getMonitorFrom('maintenancemode'))
                ->setMessage($messageBbText)
                ->setPeriod($this->project->getMonitorPeriod('maintenancemode'))
                ->setSubject($subject)
                ->setTo($to)
                ->start();

        return true;
    }

    /**
     * Start the cron mail monitor
     *
     * @return boolean True when the job was started
     */
    public function startCronMailMonitor()
    {
        $to = $this->project->getMonitorTo('cronmail');

        if (! $to) {
            return false;
        }

        $job = new MonitorJob($this->project->getName() . ' cron mail');

        switch ($this->project->getLocaleDefault()) {
            case 'nl':
                $subject = "{name} opdracht draait al meer dan {periodHours} uur";
                $messageBbText = "L.S.,

De [b]{name}[/b] opdracht heeft op {setTime} voor het laatst gedraait en zou voor {firstCheck} opnieuw gedraait moeten hebben.

Dit is waarschuwing nummer [b]{mailCount}[/b]. Controleer s.v.p. wat verkeerd gegaan is.

Dit is een automatische waarschuwing.";
                break;

            default:
                $subject = "{name} job has not run for over {periodHours} hours";
                $messageBbText = "L.S.,

The [b]{name}[/b] job ran at {setTime} for the last time and should have run again before {firstCheck}.

This is notice number {mailCount}. Please check what went wrong.

This messages was send automatically.";
                break;

        }

        $job->setFrom($this->project->getMonitorFrom('cronmail'))
                ->setMessage($messageBbText)
                ->setPeriod($this->project->getMonitorPeriod('cronmail'))
                ->setSubject($subject)
                ->setTo($to)
                ->start();

        return true;
    }
}
