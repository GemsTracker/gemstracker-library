<?php

/**
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
     * Get the mail addresses for a monitor
     *
     * @param string $monitorName ProjectSettings name
     * @param string $where Optional, a gems__staff SQL WHERE statement
     * @return boolean
     */
    protected function _getMailTo($monitorName, $where = null, $joins = '')
    {
        $projTo = $this->project->getMonitorTo($monitorName);

        if ($where) {
            $dbTo = $this->db->fetchCol(
                    "SELECT DISTINCT gsf_email FROM gems__staff $joins WHERE LENGTH(gsf_email) > 5 AND gsf_active = 1 AND $where"
                    );

            if ($dbTo) {
                if ($projTo) {
                    return array_unique(array_merge($dbTo, array_filter(array_map('trim', explode(',', $projTo)))));
                }
                return $dbTo;
            }
        }
        if ($projTo) {
            return $projTo;
        }

        return false;
    }

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
     * Return cron mail monitor
     *
     * @return MonitorJob
     */
    public function getCronMailMonitor()
    {
        return new MonitorJob($this->project->getName() . ' cron mail');
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
            return false;
        }

        $lock->lock();

        $roles = $this->util->getDbLookup()->getRolesByPrivilege('pr.maintenance.maintenance-mode');
        if ($roles) {
            $joins = "JOIN gems__groups ON gsf_id_primary_group = ggp_id_group 
                      JOIN gems__roles ON ggp_role = grl_id_role";

            $where = 'grl_name IN (' .
                    implode(', ', array_map(array($this->db, 'quote'), array_keys($roles))) .
                    ')';
        } else {
            $joins = '';
            $where = null;
        }
        $to = $this->_getMailTo('maintenancemode', $where, $joins);

        if (! $to) {
            return true;
        }

        switch ($this->project->getLocaleDefault()) {
            case 'nl':
                $initSubject = "{name} is aangezet";
                $initBbText = "L.S.,

De [b]{name}[/b] is op {setTime} aangezet.

Zolang dit aan blijft staan kan u regelmatig waarschuwingen krijgen.

Dit is een automatisch bericht.";

                $subject = "{name} staat al meer dan {periodHours} uur aan";
                $messageBbText = "L.S.,

De [b]{name}[/b] is op {setTime} aangezet en staat nog steeds aan.

Dit is waarschuwing nummer [b]{mailCount}[/b]. Controleer s.v.p. of de onderhouds modus nog steeds nodig is.

Dit is een automatische waarschuwing.";
                break;

            default:
                $initSubject = "{name} has been turned on";
                $initBbText = "L.S.,

The [b]{name}[/b] was activated at {setTime}.

As long as maintenance mode is active the system may send you warning messages.

This messages was send automatically.";

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
                ->setTo($to);

        if ($job->start()) {
            $job->sendOtherMail($initSubject, $initBbText);
        }

        return true;
    }

    /**
     * Start the cron mail monitor
     *
     * @return boolean True when the job was started
     */
    public function startCronMailMonitor()
    {
        $to = $this->_getMailTo('cronmail', 'gsf_mail_watcher = 1');

        if (! $to) {
            return false;
        }

        $job = $this->getCronMailMonitor();

        switch ($this->project->getLocaleDefault()) {
            case 'nl':
                $subject = "{name} opdracht draait al meer dan {periodHours} uur niet";
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
