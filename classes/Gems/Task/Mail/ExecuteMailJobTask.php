<?php

/**
 *
 * @package    Gems
 * @subpackage Task
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2016 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Task\Mail;

/**
 *
 * @package    Gems
 * @subpackage Task
 * @copyright  Copyright (c) 2016 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.3
 */
class ExecuteMailJobTask extends \MUtil_Task_TaskAbstract
{
    /**
     *
     * @var \Gems_User_User
     */
    protected $currentUser;

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var \Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     *
     * @param array $job
     * @param $respondentId Optional, execute for just one respondent
     * @param $organizationId Optional, execute for just one organization
     */
    public function execute($jobId = null, $respondentId = null, $organizationId = null, $preview = false)
    {
        $this->currentUser->disableMask();

        $sql = $this->db->select()->from('gems__comm_jobs')
                    ->join('gems__comm_templates', 'gcj_id_message = gct_id_template')
                    ->where('gcj_active > 0')
                    ->where('gcj_id_job = ?', $jobId);

        $job = $this->db->fetchRow($sql);

        if (empty($job)) {
            throw new \Gems_Exception($this->_('Mail job not found!'));
        }

        $mailLoader = $this->loader->getMailLoader();
        $sendById   = $job['gcj_id_user_as'];
        $sendByMail = $this->getUserEmail($sendById);
        $filter     = $this->loader->getUtil()->getMailJobsUtil()->getJobFilter($job, $respondentId, $organizationId);
        $tracker    = $this->loader->getTracker();
        $model      = $tracker->getTokenModel();

        // Fix for #680: token with the valid from the longest in the past should be the
        // used as first token and when multiple rounds start at the same date the
        // lowest round order should be used.
        $model->setSort(array('gto_valid_from' => SORT_ASC, 'gto_round_order' => SORT_ASC));

        $multipleTokensData = $model->load($filter);
        $errors             = 0;
        $mails              = 0;
        $updates            = 0;        
        $sentMailAddresses  = array();

        foreach ($multipleTokensData as $tokenData) {
            $mailer = $mailLoader->getMailer('token', $tokenData);
            /* @var $mailer \Gems_Mail_TokenMailer */
            $token  = $mailer->getToken();

            $email = $this->getToEmail($job, $sendByMail, $mailer, $token, $tokenData['can_email']);

            if (empty($email)) {
                if ($preview) {
                    $this->getBatch()->addMessage(sprintf(
                        $this->_('%s %s can not be sent because no email address is available.'), $token->getPatientNumber(), $token->getSurveyName()
                    ));
                }
                // Skip to the next token now
                continue;
            }
            
            // If the email is sent to a fall back address, we need to change it!
            if ($token->getEmail() !== $email) {
                $mailer->setTo($email, $token->getRespondentName());
            }

            $mail         = false;
            $respondentId = $token->getRespondent()->getId();
            // Add the (optional) relationid to make it unique between respondent and relations
            // Use separator that does not interfere with the numeric (and possible negative) values of both respondentid and relationid
            $respondentId .= 'R' . $token->getRelationId();

            // The variable from is used in the preview message
            $from = $this->getFromEmail($job, $sendByMail, $mailer);
            $mailer->setFrom($from);
            $mailer->setBy($sendById);

            try {
                switch ($job['gcj_process_method']) {
                    case 'M':   // Each token sends an email
                        $mail   = true;
                        break;

                    case 'A':   // Only first token mailed and marked
                        if (!isset($sentMailAddresses[$respondentId][$email])) {  // When not mailed before
                            $mail   = true;
                        }
                        break;

                    case 'O':   // Only first token mailed, all marked
                        if (!isset($sentMailAddresses[$respondentId][$email])) {  // When not mailed before
                            $mail = true;
                        } else {
                            if (!$preview) {
                                $mailer->updateToken();
                            } else {
                                $this->getBatch()->addMessage(sprintf(
                                    $this->_('Would be marked: %s %s'), $token->getPatientNumber(), $token->getSurveyName()
                                ));
                            }
                            $updates++;
                        }
                        break;

                    default:
                        throw new \Gems_Exception(sprintf($this->_('Invalid option for `%s`'), $this->_('Processing Method')));
                }

                if ($mail == true) {
                    if (!$preview) {
                        $mailer->setTemplate($job['gcj_id_message']);
                        $mailer->send();
                    } else {
                        $this->getBatch()->addMessage(sprintf(
                                $this->_('Would be sent: %s %s to %s using %s as sender'), $token->getPatientNumber(), $token->getSurveyName(), $email, $from
                        ));
                    }

                    $mails++;
                    $updates++;
                    $sentMailAddresses[$respondentId][$email] = true;
                }

            } catch (\Zend_Mail_Exception $exception) {
                $fields = $mailer->getMailFields(false);

                $info = sprintf("Error mailing to %s respondent %s with email address %s.", $fields['organization'], $fields['full_name'], $fields['email']
                );

                // Use a gems exception to pass extra information to the log
                $gemsException = new \Gems_Exception($info, 0, $exception);
                \Gems_Log::getLogger()->logError($gemsException);

                $errors++;
            }

            // Test by sending only one mail per run:
            // break;
        }

        if ($preview) {
            $this->getBatch()->addMessage(sprintf(
                    $this->_('Would send %d e-mails with template %s, and update %d tokens.'),
                    $mails,
                    $job['gct_name'],
                    $updates
                    ));
        } else {
            $this->getBatch()->addMessage(sprintf(
                    $this->_('Sent %d e-mails with template %s, updated %d tokens.'),
                    $mails,
                    $job['gct_name'],
                    $updates
                    ));
        }

        if ($errors) {
            $this->getBatch()->addMessage(sprintf(
                    $this->_('%d error(s) occurred while creating mails for template %s. Check error log for details.'),
                    $errors,
                    $job['gct_name']
                    ));
        }

        $this->currentUser->enableMask();
    }

    /**
     *
     * @param array $job
     * @param string $sendByMail Email address
     * @param \Gems_Mail_TokenMailer $mailer
     * @return string or null
     * @throws \Gems_Exception
     */
    protected function getFallbackEmail(array $job, $sendByMail, \Gems_Mail_TokenMailer $mailer)
    {
        // Set the from address to use in this job
        switch ($job['gcj_fallback_method']) {
            case 'O':   // Send on behalf of organization
                return $mailer->getOrganization()->getEmail(); //$organization->getName() . ' <' . $organization->getEmail() . '>';

            case 'U':   // Send on behalf of fixed user
                return $sendByMail;

            case 'F':   // Send on behalf of fixed email address
                return $job['gcj_fallback_fixed'];

            case 'S':   // Use site email
                return $this->project->email['site'];

            default:
                throw new \Gems_Exception(sprintf($this->_('Invalid option for `%s`'), $this->_('Fallback address used')));
        }
    }

    /**
     *
     * @param array $job
     * @param string $sendByMail Email address
     * @param \Gems_Mail_TokenMailer $mailer
     * @return string or null
     * @throws \Gems_Exception
     */
    protected function getFromEmail(array $job, $sendByMail, \Gems_Mail_TokenMailer $mailer)
    {
        // Set the from address to use in this job
        switch ($job['gcj_from_method']) {
            case 'O':   // Send on behalf of organization
                return $mailer->getOrganization()->getEmail(); //$organization->getName() . ' <' . $organization->getEmail() . '>';

            case 'U':   // Send on behalf of fixed user
                return $sendByMail;

            case 'F':   // Send on behalf of fixed email address
                return $job['gcj_from_fixed'];

            case 'S':   // Use site email
                return $this->project->email['site'];

            default:
                throw new \Gems_Exception(sprintf($this->_('Invalid option for `%s`'), $this->_('From address used')));
        }
    }

    /**
     *
     * @param array $job
     * @param string $sendByMail Email address
     * @param \Gems_Mail_TokenMailer $mailer
     * @param \Gems_Tracker_Token $token
     * @param boolean $canBeMailed True when allowed to mail respondent
     * @return string or null
     * @throws \Gems_Exception
     */
    protected function getToEmail(array $job, $sendByMail, \Gems_Mail_TokenMailer $mailer, \Gems_Tracker_Token $token, $canBeMailed)
    {
        $email = null;

        switch ($job['gcj_target']) {
            case '0':
                if ($canBeMailed) {
                    $email = $token->getEmail();
                }
                break;

            case '1':
                if($canBeMailed && $token->hasRelation()) {
                    $email = $token->getRelation()->getEmail();
                }
                break;

            case '2':
                if ($canBeMailed) {
                    $email = $token->getRespondent()->getEmailAddress();
                }
                break;

            case '3':
                return $this->getFallbackEmail($job, $sendByMail, $mailer);

            default:
                throw new \Gems_Exception(sprintf($this->_('Invalid option for `%s`'), $this->_('Filler')));
        }


        switch ($job['gcj_to_method']) {
            case 'A':
                return $email;

            case 'O':
                if ($email) {
                    return $email;
                }
                // Intentional fall through
            case 'F':
                return $this->getFallbackEmail($job, $sendByMail, $mailer);

            default:
                throw new \Gems_Exception(sprintf($this->_('Invalid option for `%s`'), $this->_('Addresses used')));
        }
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

}
