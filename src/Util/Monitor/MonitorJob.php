<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Util\Monitor
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Util\Monitor;

use Symfony\Component\Mailer\Mailer;
use Zalt\File\File;

/**
 * @package    Gems
 * @subpackage Util\Monitor
 * @since      Class available since version 1.0
 */
class MonitorJob
{
    /**
     * The time when the job rerun is too late
     *
     * Set internally only
     *
     * @var int
     */
    protected int $checkTime;

    /**
     * The first time when the job rerun is too late
     *
     * Set internally only
     *
     * @var int
     */
    protected int $firstCheck;

    /**
     * From who we send the message, default is system default
     *
     * @var string
     */
    protected string $from;

    /**
     * The number of times this message was mailed
     *
     * @var int
     */
    protected int $mailCount = 0;

    /**
     * Content of the message, you can use the variables returned by getMailVariables()
     *
     * @see getMailVariables()
     * @var string
     */
    protected string $message = "L.S.,

The <b>{name}</b> ran at {setTime} and should have run again before {firstCheck}.

This is notice number <b>{mailCount}</b>. Please check what went wrong.

This messages was send automatically.";

    /**
     * Date format for used dates
     *
     * @var string
     */
    public static string $monitorDateFormat = 'r';

    /**
     * Directory where the monitor file is saved.
     *
     * Is created when it does not exist
     *
     * @var string
     */
    public static string $monitorDir;

    /**
     * Filename for the monitor file
     *
     * @var string
     */
    public static string $monitorFilename = 'monitor.json';

    /**
     * Period in seconds, default is 25 hours
     *
     * @var int
     */
    protected int $period = 90000;

    /**
     * The time when the job was set
     *
     * Set internally only
     *
     * @var int
     */
    protected int $setTime;

    /**
     * Subject of the message, you can use the variables returned by getMailVariables()
     *
     * @see getMailVariables()
     * @var string
     */
    protected string $subject = "{name} has not run for over {periodHours} hours";

    /**
     * To whom we send the message
     *
     * @var array
     */
    protected array $to = [];

    /**
     *
     * @param string $name Name of the job
     * @param array $settings Optional new variables for the job
     */
    public function __construct(
        protected string $name,
        array $settings
    )
    {
        if ($settings) {
            $this->exchangeArray($settings);
        }
    }

    /**
     * Load the monitors from file and return then as an array
     *
     * @return array
     */
    private static function _getMonitors(): array
    {
        if (! self::$monitorDir) {
            self::$monitorDir = getcwd();
        }

        $file = self::$monitorDir . DIRECTORY_SEPARATOR . self::$monitorFilename;
        if (file_exists($file)) {
            return json_decode(file_get_contents($file), true);
        } else {
            return [];
        }
    }

    /**
     * Send the actual mail
     *
     * @param string $subject
     * @param string $message BB Code message
     */
    private function _sendMail($subject, $message): void
    {
        // Send a seperate mail to each recipient, otherwise they might do nothing
        foreach ($this->to as $to) {
            $mail = new \MUtil\Mail();
            $mail->addTo($to);

            if ($this->from) {
                $mail->setFrom($this->from);
            } else {
                $mail->setFromToDefaultFrom();
            }

            $replacements = $this->getMailVariables();

            $mail->setSubject(strtr($subject, $replacements));
            $mail->setBodyHtml(strtr($message, $replacements));
            $mail->send();
        }
    }

    /**
     * Save the monitors to the monitor file.
     *
     * @param array $monitors
     */
    private static function _setMonitors(array $monitors): void
    {
        if (! self::$monitorDir) {
            self::$monitorDir = getcwd();
        } else {
            File::ensureDir(self::$monitorDir);
        }

        $file = self::$monitorDir . DIRECTORY_SEPARATOR . self::$monitorFilename;

        file_put_contents($file, json_encode($monitors));
    }

    /**
     * Performs a check for all set monitors
     *
     * @return array of messages
     */
    public static function checkJobs()
    {
        $output   = [];
        $monitors = self::_getMonitors();

        foreach ($monitors as $name => $data) {
            $job = new self($name, $data);

            if ($job->isOverdue()) {
                if ($job->sendOverdueMail()) {
                    $monitors[$name] = $job->getArrayCopy();
                    $output[$name] = "Job $name was triggered.";
                }
            }
        }

        if ($output) {
            self::_setMonitors($monitors);

            // \MUtil\EchoOut\EchoOut::track($output);
            return $output;
        }

        $message = sprintf("No jobs where triggered out of %s.", count($monitors));

        // \MUtil\EchoOut\EchoOut::track($message);
        return [$message];
    }

    /**
     * Exchange the set values for new values.
     *
     * @param array $input
     * @return array
     */
    public function exchangeArray(array $input): array
    {
        $old = $this->getArrayCopy();

        foreach ($input as $name => $value) {
            $this->$name = $value;
        }

        return $old;
    }

    /**
     * Create an array containing all saveable variables
     *
     * @return array
     */
    public function getArrayCopy(): array
    {
        return get_object_vars($this);
    }

    /**
     * Retrieve a MonitorJob by name
     *
     * @param string $jobName
     * @return MonitorJob
     */
    public static function getJob($jobName): MonitorJob
    {
        $monitors = self::_getMonitors();

//        dump($jobName, $monitors);
        if (array_key_exists($jobName, $monitors)) {
            $data = $monitors[$jobName];
        } else {
            $data = [];
        }

        return new self($jobName, $data);
    }

    /**
     * Create an array containing all saveable variables
     *
     * {firstCheck} => When the signal should have been set again
     * {mailCount} => The number of time a message was send, 1 for the first time.
     * {name} => The string description of the job
     * {periodHours} => The period since the signal was set
     * {setTime} => The time that signal was set
     *
     * @return array
     */
    public function getMailVariables(): array
    {
        $time = time();

        if (! $this->firstCheck) {
            $this->firstCheck = $time + $this->period;
        }
        if (! $this->setTime) {
            $this->setTime = $time;
        }

        return [
            '{firstCheck}'  => date(self::$monitorDateFormat, $this->firstCheck),
            '{mailCount}'   => $this->mailCount + 1,
            '{name}'        => $this->name,
            '{periodHours}' => intval(($time - $this->setTime) / 3600),
            '{setTime}'     => date(self::$monitorDateFormat, $this->setTime),
        ];
    }

    /**
     * The name of the job
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Check: is this job overdue?
     *
     * @return boolean
     */
    public function isOverdue(): bool
    {
        return ($this->checkTime < time());
    }

    /**
     * Send another mail to the monitors
     *
     * @see getMailVariables()
     * @param string $subject
     * @param string $bbMessage BB message string
     * @return boolean True when object has changed
     */
    public function sendOtherMail($subject, $bbMessage): bool
    {
        $this->_sendMail($subject, $bbMessage);

        return true;
    }

    /**
     * Send the mail for an overdue job
     *
     * @return boolean True when object has changed
     */
    public function sendOverdueMail(): bool
    {
        $this->_sendMail($this->subject, $this->message);

        $this->checkTime = time() + $this->period;
        $this->mailCount++;

        return true;
    }

    /**
     * Set the from mail address
     *
     * @param string $from
     * @return MonitorJob
     */
    public function setFrom($from): MonitorJob
    {
        $this->from = $from;

        return $this;
    }

    /**
     * The message text as BB code, you can use the variables returned by getMailVariables()
     *
     * @see getMailVariables()
     * @param string $bbMessage BB message string
     * @return MonitorJob
     */
    public function setMessage($bbMessage): MonitorJob
    {
        $this->message = $bbMessage;

        return $this;
    }

    /**
     * Set the from mail address
     *
     * @param mixed $period When a string ending with 'd', 'h' or 'm' in days, hours or minutes, otherwise seconds
     * @return MonitorJob
     */
    public function setPeriod($period): MonitorJob
    {
        if (strlen($period)) {
            if ('never' == $period) {
                $this->period = -1;
            } else {
                switch (strtolower(substr($period, -1))) {
                    case 'd':
                        $this->period = intval(substr($period, 0, -1)) * 86400;
                        break;

                    case 'h':
                        $this->period = intval(substr($period, 0, -1)) * 3600;
                        break;

                    case 'm':
                        $this->period = intval(substr($period, 0, -1)) * 60;
                        break;

                    default:
                        $this->period = intval($period);
                        break;
                }
            }
        }

        return $this;
    }

    /**
     * The subject text, you can use the variables returned by getMailVariables()
     *
     * @see getMailVariables()
     * @param string $subject
     * @return MonitorJob
     */
    public function setSubject($subject): MonitorJob
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * The addresses to mail to
     *
     * @param mixed $to string with multiple addresses seperated by , or array
     * @return MonitorJob
     */
    public function setTo($to)
    {
        if (! is_array($to)) {
            $to = explode(',', $to);
        }
        $this->to = array_filter(array_map('trim', $to));

        return $this;
    }

    /**
     * Start monitoring
     *
     * @return boolean True when job started
     */
    public function start(): bool
    {
        return self::startJob($this);
    }

    /**
     * Start monitoring
     *
     * @param MonitorJob $job
     * @return boolean True when job started
     */
    public static function startJob(MonitorJob $job): bool
    {
        // Period has to be a positive integer
        if ($job->period < 0) {
            return false;
        }

        $monitors = self::_getMonitors();

        $time = time();
        $job->checkTime  = $time + $job->period;
        $job->firstCheck = $job->checkTime;
        $job->mailCount  = 0;
        $job->setTime    = $time;

        $monitors[$job->name] = $job->getArrayCopy();

        self::_setMonitors($monitors);

        return true;
    }

    /**
     * Stop monitoring
     *
     * @return MonitorJob
     */
    public function stop(): MonitorJob
    {
        self::stopJob($this);

        return $this;
    }

    /**
     * Remove a signal to monitor
     *
     * @param MonitorJob $job
     */
    public static function stopJob(MonitorJob $job): void
    {
        $monitors = self::_getMonitors();
        $name     = $job->name;

        if (isset($monitors[$name])) {
            unset($monitors[$name]);
            self::_setMonitors($monitors);
        }
    }
}