<?php

/**
 *
 * @package    Gems
 * @subpackage Util
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Util\Monitor;

use Gems\Db\ResultFetcher;
use Gems\Util\Lock\MaintenanceLock;
use Laminas\Permissions\Acl\Acl;

/**
 *
 *
 * @package    Gems
 * @subpackage Util
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Mar 2, 2016 1:42:12 PM
 */
class Monitor
{
    public function __construct(
        protected readonly array $config,
        protected readonly Acl $acl,
        protected readonly ResultFetcher $resultFetcher,
        protected readonly MaintenanceLock $maintenanceLock,
    )
    {
        MonitorJob::$monitorDateFormat  = 'l j F Y H:i';
        MonitorJob::$monitorDir         = $this->config['rootDir'] . '/data';
    }

    /**
     * Get the mail addresses for a monitor
     *
     * @param string $monitorName ProjectSettings name
     * @return array
     */
    protected function _getMailTo($monitorName): array
    {
        $projTo  = array_filter(explode(',', $this->getTo($monitorName)));
        $userTo  = $this->_getUserTo($monitorName);
        $orgTo   = $this->_getOrgTo($monitorName);
        $mailtos = array_merge($projTo, $userTo, $orgTo);

        return array_values(array_unique(array_filter(array_map('trim',$mailtos))));
    }

    /**
     * Return an array of organization recipients for the given monitorName
     *
     * @param string $monitorName
     * @return array
     */
    protected function _getOrgTo($monitorName): array
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

        $orgTo = $this->resultFetcher->fetchCol(
            "SELECT DISTINCT gor_contact_email FROM gems__organizations WHERE LENGTH(gor_contact_email) > 5 AND gor_active = 1 AND $where"
        );

        return $orgTo;
    }

    /**
     * Return an array of user recipients for the given monitorName
     *
     * @param string $monitorName
     * @return array
     */
    protected function _getUserTo($monitorName): array
    {

        switch ($monitorName) {
            case 'maintenancemode':
                $platform = $this->resultFetcher->getPlatform();
                $roles    = [];
                foreach ($this->acl->getRoles() as $role) {
                    if ($this->acl->isAllowed($role, 'pr.maintenance.maintenance-mode')) {
                        $roles[$role] = $platform->quoteValue($role);
                    }
                }

                if ($roles) {
                    $joins = "JOIN gems__groups ON gsf_id_primary_group = ggp_id_group 
                      JOIN gems__roles ON ggp_role = grl_id_role";

                    $where = 'grl_name IN (' .
                            implode(', ', $roles) .
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

        $userTo = $this->resultFetcher->fetchCol(
                "SELECT DISTINCT gsf_email FROM gems__staff $joins WHERE LENGTH(gsf_email) > 5 AND gsf_active = 1 AND $where"
                );
        return $userTo;
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
    public function getCronMailMonitor(): MonitorJob
    {
        return MonitorJob::getJob($this->getAppName() . ' cron mail');
    }

    /**
     * Return the mail template to use for sending CronMailMonitor messages
     *
     * @param string $locale The locale to use for the message
     *
     * @return array with elements $subject and $messageHtml
     */
    public function getCronMailTemplate(string $locale): array
    {
        switch ($locale) {
            case 'nl':
                $subject = "{name} opdracht draait al meer dan {periodHours} uur niet";
                $messageHtml = "L.S.,

De <b>{name}</b> opdracht heeft op {setTime} voor het laatst gedraaid en zou voor {firstCheck} opnieuw gedraaid moeten hebben.

Dit is waarschuwing nummer <b>{mailCount}</b>. Controleer s.v.p. wat verkeerd gegaan is.

Dit is een automatische waarschuwing.";
                break;

            default:
                $subject = "{name} job has not run for over {periodHours} hours";
                $messageHtml = "L.S.,

The <b>{name}</b> job ran at {setTime} for the last time and should have run again before {firstCheck}.

This is notice number {mailCount}. Please check what went wrong.

This messages was send automatically.";
                break;
        }

        return array($subject, $messageHtml);
    }

    protected function getDefaultLocale(): string
    {
        if (isset($this->config['locale']['default'])) {
            return $this->config['locale']['default'];
        }
        return 'en';
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

    /**
     *
     * @return MonitorJob
     */
    public function getMaintenanceMonitor()
    {
        return MonitorJob::getJob($this->getAppName() . ' maintenance mode');
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
     * Return the mail template to use for sending ReverseMaintenanceMonitor messages
     *
     * There are two messages, the message when the maintenance mode is first turned on
     * and the one that is sent after the set amount of time when the maintenance mode
     * is still turned on.
     *
     * @param string $locale The locale to use for the message
     *
     * @return array with elements $initSubject, $initHtml, $subject and $messageHtml
     */
    public function getReverseMaintenanceMonitorTemplate(string $locale): array
    {
        switch ($locale) {
            case 'nl':
                $initSubject = "{name} is aangezet";
                $initHtml = "L.S.,

De <b>{name}</b> is op {setTime} aangezet.

Zolang dit aan blijft staan kan u regelmatig waarschuwingen krijgen.

Dit is een automatisch bericht.";

                $subject = "{name} staat al meer dan {periodHours} uur aan";
                $messageHtml = "L.S.,

De <b>{name}</b> is op {setTime} aangezet en staat nog steeds aan.

Dit is waarschuwing nummer <b>{mailCount}</b>. Controleer s.v.p. of de onderhouds modus nog steeds nodig is.

Dit is een automatische waarschuwing.";
                break;

            default:
                $initSubject = "{name} has been turned on";
                $initHtml = "L.S.,

The <b>{name}</b> was activated at {setTime}.

As long as maintenance mode is active the system may send you warning messages.

This messages was send automatically.";

                $subject = "{name} has been active for over {periodHours} hours";
                $messageHtml = "L.S.,

The <b>{name}</b> was activated at {setTime} and is still active.

This is notice number {mailCount}. Please check whether the maintenance mode is still required.

This messages was send automatically.";
                break;

        }

        return array($initSubject, $initHtml, $subject, $messageHtml);
    }

    protected function getTo(string $name): string
    {
        if (isset($this->config['monitor'][$name]) && isset($this->config['monitor'][$name]['to']) && $this->config['monitor'][$name]['to']) {
            return $this->config['monitor'][$name]['to'];
        }

        if (isset($this->config['monitor']['default']) && isset($this->config['monitor']['default']['to']) && $this->config['monitor']['default']['to']) {
            return $this->config['monitor']['default']['to'];
        }

        return '';
    }

    /**
     * Start the cron mail monitor
     *
     * @return boolean True when the job was started
     */
    public function reverseMaintenanceMonitor(): bool
    {
        $job  = $this->getMaintenanceMonitor();

        if ($this->maintenanceLock->isLocked()) {
            $job->stop();
            $this->maintenanceLock->unlock();
            return false;
        }

        $this->maintenanceLock->lock();
        $to = $this->_getMailTo('maintenancemode');

        if (!$to) {
            return true;
        }

        list($initSubject, $initHtml, $subject, $messageHtml) = $this->getReverseMaintenanceMonitorTemplate($this->getDefaultLocale());

        $job->setFrom($this->getFrom('maintenancemode'))
                ->setMessage($messageHtml)
                ->setPeriod($this->getPeriod('maintenancemode'))
                ->setSubject($subject)
                ->setTo($to);

        if ($job->start()) {
            $job->sendOtherMail($initSubject, $initHtml);
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

        list($subject, $messageHtml) = $this->getCronMailTemplate($this->getDefaultLocale());

        $job->setFrom($this->getFrom('cronmail'))
                ->setMessage($messageHtml)
                ->setPeriod($this->getPeriod('cronmail'))
                ->setSubject($subject)
                ->setTo($to)
                ->start();

        return true;
    }
}
