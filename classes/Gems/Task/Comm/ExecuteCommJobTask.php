<?php

/**
 *
 * @package    Gems
 * @subpackage task\Comm
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2021, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Task\Comm;

use Gems\Exception\ClientException;

/**
 *
 * @package    Gems
 * @subpackage Communication
 * @license    New BSD License
 * @since      Class available since version 1.9.1
 */
class ExecuteCommJobTask extends \MUtil\Task\TaskAbstract
{
    /**
     *
     * @var \Gems\User\User
     */
    protected $currentUser;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     *
     * @var \Gems\Project\ProjectSettings
     */
    protected $project;

    /**
     * @var \Gems\Util
     */
    protected $util; 

    /**
     *
     * @param array $job
     * @param $respondentId int Optional, execute for just one respondent
     * @param $organizationId int Optional, execute for just one organization
     * @param boolean $preview Preview  mode active
     * @param boolean $forceSent Ignore previous sent mails
     */
    public function execute($jobId = null, $respondentId = null, $organizationId = null, $preview = false, $forceSent = false)
    {
        $this->currentUser->disableMask();

        $this->getBatch()->addToCounter('jobs_started', 1);
        
        $jobsUtil = $this->util->getCommJobsUtil();
        $tracker  = $this->loader->getTracker();

        $job = $jobsUtil->getJob($jobId);

        if (empty($job)) {
            throw new \Gems\Exception($this->_('Mail job not found!'));
        }

        $multipleTokensData = $jobsUtil->getTokenData($job, $respondentId, $organizationId, $forceSent);

        $errors             = 0;
        $communications     = 0;
        $updates            = 0;
        $sentContactData    = [];

        foreach ($multipleTokensData as $tokenData) {
            $token = $this->loader->getTracker()->getToken($tokenData['gto_id_token']);

            $contactData = $job['gcj_target'] . $job['gcj_to_method'];

            $communicate = false;
            $respondentId = $token->getRespondent()->getId();
            // Add the (optional) relationid to make it unique between respondent and relations
            // Use separator that does not interfere with the numeric (and possible negative) values of both respondentid and relationid
            $respondentId .= 'R' . $token->getRelationId();

            switch ($job['gcj_process_method']) {
                case 'M':   // Each token sends an email
                    $communicate = true;
                    break;

                case 'A':   // Only first token mailed and marked
                    if (!isset($sentContactData[$respondentId][$contactData])) {  // When not contacted before
                        $communicate = true;
                    }
                    break;

                case 'O':   // Only first token mailed, all marked
                    if (!isset($sentContactData[$respondentId][$contactData])) {  // When not contacted before
                        $communicate = true;
                    } else {
                        if (!$preview) {
                            $this->incrementTokenCommunicationCount($token->getTokenId());
                        } else {
                            $this->getBatch()->addMessage(sprintf(
                                $this->_('Would be marked: %s %s'), $token->getPatientNumber(), $token->getSurveyName()
                            ));
                        }
                        $updates++;
                    }
                    break;

                default:
                    throw new \Gems\Exception(sprintf($this->_('Invalid option for `%s`'), $this->_('Processing Method')));
            }

            if ($communicate == true) {
                $messenger = $jobsUtil->getJobMessenger($job);
                $messenger->setBatch($this->getBatch());
                
                $result = $messenger->sendCommunication($job, $tokenData, $preview);

                if ($result === false) {
                    $errors++;
                } else {
                    if (!$preview) {
                        $this->getBatch()->addToCounter('communications_sent', 1);
                    }

                    $communications++;
                    $updates++;
                    $sentContactData[$respondentId][$contactData] = true;
                }
            }

            // Test by sending only one mail per run:
            // break;
        }

        $topic = $this->getTopic();


        if ($preview) {
            $this->getBatch()->addMessage(sprintf(
                $this->_('Would send %d %s with template %s, and update %d tokens.'),
                $communications,
                $topic,
                $job['gct_name'],
                $updates
            ));
        } else {
            $this->getBatch()->addMessage(sprintf(
                $this->_('Sent %d %s with template %s, updated %d tokens.'),
                $communications,
                $topic,
                $job['gct_name'],
                $updates
            ));
        }

        if ($errors) {
            $this->getBatch()->addMessage(sprintf(
                $this->_('%d error(s) occurred while creating %s for template %s. Check error log for details.'),
                $errors,
                $topic,
                $job['gct_name']
            ));
        }

        $this->currentUser->enableMask();
    }

    /**
     * @param array $job
     * @param array $tokenData
     * @param $preview
     * @return \Gems\Communication\JobMessenger\JobMessengerAbstract|null
     */
    protected function getJobMessenger(array $job, array $tokenData, $preview)
    {
        $messengerName = $job['gcm_type'];

        $messenger = $this->loader->getCommunicationLoader()->getJobMessenger($messengerName);
        $messenger->setBatch($this->getBatch());
        return $messenger;
    }

    protected function getTopic()
    {
        return 'communications';
    }

    /**
     * Update the token data when a Mail has been sent.
     * @param  integer $tokenId TokenId to update. If none is supplied, use the current token
     */
    public function incrementTokenCommunicationCount($tokenId)
    {
        $tokenData['gto_mail_sent_num'] = new \Zend_Db_Expr('gto_mail_sent_num + 1');
        $now = new \DateTimeImmutable();
        $tokenData['gto_mail_sent_date'] = $now->format('Y-m-d');

        $this->db->update('gems__tokens', $tokenData, $this->db->quoteInto('gto_id_token = ?', $tokenId));
    }
}
