<?php

/**
 *
 * @package    Gems
 * @subpackage Mail
 * @author     Jasper van Gestel <jappie@dse.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Mail;

use MUtil\Translate\TranslateableTrait;

/**
 *
 *
 * @package    Gems
 * @subpackage Mail
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class MailLoader extends \Gems\Loader\TargetLoaderAbstract
{
    use TranslateableTrait;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * Allows sub classes of \Gems\Loader\LoaderAbstract to specify the subdirectory where to look for.
     *
     * @var string $cascade An optional subdirectory where this subclass always loads from.
     */
    protected $cascade = 'Mail';

    /**
     * @var null|Psr\Log\LoggerInterface
     */
    public $cronLog = null;

    /**
     *
     * @var \Gems\User\User
     */
    protected $currentUser;

    /**
     *
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     *
     * @var array Define the mail target options
     */
    protected $mailTargets = array(
        'staff' => 'Staff',
        'respondent' => 'Respondent',
        'token' => 'Token',
        'staffPassword' => 'Password reset',
    );


    /**
     * Perform automatic job mail
     */
    public function getCronBatch($id = 'cron')
    {
        $batch = $this->loader->getTaskRunnerBatch($id);
        if ($this->cronLog instanceof \Psr\Log\LoggerInterface) {
            $batch->setMessageLogger($this->cronLog);
        }
        $batch->minimalStepDurationMs = 3000; // 3 seconds max before sending feedback

        if (! $batch->isLoaded()) {
            $this->loadCronBatch($batch);
        }

        return $batch;
    }

    /**
     * Return the mail elements helper class
     * @return \Gems\Mail\MailElements
     */
    public function getMailElements()
    {
        return $this->_loadClass('MailElements');
    }

    /**
     * Get the correct mailer class from the given target
     * @param  [type] $target      mailtarget (lowercase)
     * @param  array  $identifiers the identifiers needed for the specific mailtargets
     * @return \Gems\Mail\MailerAbstract class
     */
    public function getMailer($target = null, $id = false, $orgId = false)
    {
        if(isset($this->mailTargets[$target])) {
            $target = ucfirst($target);
            return $this->_loadClass($target.'Mailer', true, array($id, $orgId));
        } else {
            return false;
        }
    }

    /**
     * Get the possible mail targets
     *
     * @return Array  mail targets
     */
    public function getMailTargets()
    {
        return $this->mailTargets;
    }

    /**
     * Get default mailform
     *
     * @return \Gems\Mail_MailForm
     */
    public function getMailForm()
    {
        return $this->_loadClass('MailForm');
    }

    /**
     * Perform the actions and load the tasks needed to start the cron batch
     *
     * @param \Gems\Task\TaskRunnerBatch $batch
     */
    protected function loadCronBatch(\Gems\Task\TaskRunnerBatch $batch)
    {
       $batch->addMessage(sprintf($this->_("Starting mail jobs")));
       $batch->addTask('Mail\\AddAllMailJobsTask');

        // Check for unprocessed tokens, 
       $tracker = $this->loader->getTracker();
       $tracker->loadCompletedTokensBatch($batch, null, $this->currentUser->getUserId());
   }
}