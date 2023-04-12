<?php

/**
 *
 * @package    Gems
 * @subpackage Util
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Util;

use MUtil\Util\MonitorJob;

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
     * @var array
     */
    protected $config;
    /**
     *
     * @var \Zend_Locale
     */
    protected $locale;

    /**
     *
     * @var \Gems\Util
     */
    protected $util;
    
    /**
     * Return an array of organization recipients for the given monitorName
     * 
     * @param string $monitorName
     * @return array
     */
    protected function _getOrgTo($monitorName)
    {
        switch ($monitorName) {
            case 'maintenancemode':
                $where = "1 = 0";
                break;

            case 'cronmail':
            default:
                $where = 'gor_mail_watcher = 1';
                break;
        }
        
        $orgTo = $this->db->fetchCol(
                "SELECT DISTINCT gor_contact_email FROM gems__organizations WHERE LENGTH(gor_contact_email) > 5 AND gor_active = 1 AND $where"
                );
        return $orgTo;        
    }

    /**
     * Get the mail addresses for a monitor
     *
     * @param string $monitorName ProjectSettings name
     * @param string $where Optional, a gems__staff SQL WHERE statement
     * @return array
     */
    protected function _getMailTo($monitorName)
    {     
        $projTo  = explode(',',$this->getTo($monitorName));
        $userTo  = $this->_getUserTo($monitorName);
        $orgTo   = $this->_getOrgTo($monitorName);
        $mailtos = array_merge($projTo, $userTo, $orgTo);
        
        return array_values(array_unique(array_filter(array_map('trim',$mailtos))));
    }
    
    /**
     * Return an array of user recipients for the given monitorName
     * 
     * @param string $monitorName
     * @return array
     */
    protected function _getUserTo($monitorName)
    {
        switch ($monitorName) {
            case 'maintenancemode':
                $roles = $this->util->getDbLookup()->getRolesByPrivilege('pr.maintenance.maintenance-mode');
                if ($roles) {
                    $joins = "JOIN gems__groups ON gsf_id_primary_group = ggp_id_group 
                      JOIN gems__roles ON ggp_role = grl_id_role";

                    $where = 'grl_name IN (' .
                            implode(', ', array_map(array($this->db, 'quote'), array_keys($roles))) .
                            ')';
                } else {
                    return [];
                }
                break;

            case 'cronmail':
            default:
                $joins = '';
                $where = 'gsf_mail_watcher = 1';
                break;
        }
        
        $userTo = $this->db->fetchCol(
                "SELECT DISTINCT gsf_email FROM gems__staff $joins WHERE LENGTH(gsf_email) > 5 AND gsf_active = 1 AND $where"
                );
        return $userTo;
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
        MonitorJob::$monitorDir         = $this->config['rootDir'] . '/var/settings';
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

    protected function getAppName(): ?string
    {
        if (isset($this->config['app']['name'])) {
            return $this->config['app']['name'];
        }
        return null;
    }

    /**
     * Return cron mail monitor
     *
     * @return MonitorJob
     */
    public function getCronMailMonitor()
    {
        return MonitorJob::getJob($this->getAppName() . ' cron mail');
    }
    
    /**
     * Return the mail template to use for sending CronMailMonitor messages
     * 
     * @param string $locale The locale to use for the message
     * 
     * @return array with elements $subject and $messageBbText
     */
    public function getCronMailTemplate($locale)
    {
        switch ($locale) {
            case 'nl':
                $subject = "{name} opdracht draait al meer dan {periodHours} uur niet";
                $messageBbText = "L.S.,

De [b]{name}[/b] opdracht heeft op {setTime} voor het laatst gedraaid en zou voor {firstCheck} opnieuw gedraaid moeten hebben.

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
        
        return array($subject, $messageBbText);
    }

    protected function getFrom($name)
    {
        if (isset($this->config['monitor'][$name], $this->config['monitor'][$name]['from'])) {
            return $this->config['monitor'][$name]['from'];
        }

        if (isset($this->config['monitor']['default'], $this->config['monitor']['default']['from'])) {
            return $this->config['monitor']['default']['from'];
        }

        return 'noreply@gemstracker.org';
    }

    protected function getPeriod(string $name)
    {
        if (isset($this->config['monitor'][$name], $this->config['monitor'][$name]['period'])) {
            return $this->config['monitor'][$name]['period'];
        }

        if (isset($this->config['monitor']['default'], $this->config['monitor']['default']['period'])) {
            return $this->config['monitor']['default']['period'];
        }

        return '25h';
    }

    /**
     * 
     * @return MonitorJob
     */
    public function getReverseMaintenanceMonitor()
    {
       return MonitorJob::getJob($this->getAppName() . ' maintenance mode');
    }
    
    /**
     * Return the mail template to use for sending ReverseMaintenanceMonitor messages
     * 
     * There are two messages, the message when the maintenance mode is first turned on
     * and the one that is sent after the set amount of time when the maintenance mode
     * is still turned on.
     * 
     * @param string $locale The locale to use for the message
     * 
     * @return array with elements $initSubject, $initBbText, $subject and $messageBbText
     */
    public function getReverseMaintenanceMonitorTemplate($locale)
    {
        switch ($locale) {
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

        return array($initSubject, $initBbText, $subject, $messageBbText);
    }

    protected function getTo(string $name)
    {
        if (isset($this->config['monitor'][$name], $this->config[$name]['to'])) {
            return $this->config['monitor'][$name]['to'];
        }

        if (isset($this->config['monitor']['default'], $this->config['monitor']['default']['to'])) {
            return $this->config['monitor']['default']['to'];
        }

        return null;
    }

    /**
     * Start the cron mail monitor
     *
     * @return boolean True when the job was started
     */
    public function reverseMaintenanceMonitor()
    {
        $job  = $this->getReverseMaintenanceMonitor();
        $lock = $this->util->getMaintenanceLock();

        if ($lock->isLocked()) {
            $job->stop();
            $lock->unlock();
            return false;
        }

        $lock->lock();
        $to = $this->_getMailTo('maintenancemode');
        
        if (!$to) {
            return true;
        }

        $locale = $this->project->getLocaleDefault();
        list($initSubject, $initBbText, $subject, $messageBbText) = $this->getReverseMaintenanceMonitorTemplate($locale);

        $job->setFrom($this->getFrom('maintenancemode'))
                ->setMessage($messageBbText)
                ->setPeriod($this->getPeriod('maintenancemode'))
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
        $to  = $this->_getMailTo('cronmail');
        $job = $this->getCronMailMonitor();

        if (! $to) {
            $job->stop();
            return false;
        }

        $locale = $this->project->getLocaleDefault();
        list($subject, $messageBbText) = $this->getCronMailTemplate($locale);        

        $job->setFrom($this->getFrom('cronmail'))
                ->setMessage($messageBbText)
                ->setPeriod($this->getPeriod('cronmail'))
                ->setSubject($subject)
                ->setTo($to)
                ->start();

        return true;
    }
}
