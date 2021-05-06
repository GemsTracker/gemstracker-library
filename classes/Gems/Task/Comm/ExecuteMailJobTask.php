<?php

namespace Gems\Task\Comm;


class ExecuteMailJobTask extends ExecuteCommJobTask
{
    protected function sendCommunication(array $job, array $tokenData, $preview)
    {
        $mailLoader = $this->loader->getMailLoader();
        $mailer       = $mailLoader->getMailer('token', $tokenData['gto_id_token']);
        /* @var $mailer \Gems_Mail_TokenMailer */
        $token  = $mailer->getToken();

        $sendById   = $job['gcj_id_user_as'];
        $sendByMail = $this->getUserEmail($sendById);

        $email = $this->getToEmail($job, $sendByMail, $mailer, $token, $tokenData['can_email']);

        if (empty($email)) {
            if ($preview) {
                $this->getBatch()->addMessage(sprintf(
                    $this->_('%s %s can not be sent because no email address is available.'), $token->getPatientNumber(), $token->getSurveyName()
                ));
            }
            // Skip to the next token now
            return null;
        }

        // If the email is sent to a fall back address, we need to change it!
        if ($token->getEmail() !== $email) {
            $mailer->setTo($email, $token->getRespondentName());
        }

        // The variable from is used in the preview message
        $from = $this->getFromEmail($job, $sendByMail, $mailer);
        $fromName = $this->getFromName($job, $sendByMail, $mailer);

        if ($preview) {
            $this->getBatch()->addMessage(sprintf(
                $this->_('Would be sent: %s %s to %s using %s as sender'), $token->getPatientNumber(), $token->getSurveyName(), $email, $from
            ));
        } else {
            try {
                $mailer->setFrom($from, $fromName);

                $mailer->setBy($sendById);

                $mailer->setTemplate($job['gcj_id_message']);
                $mailer->setMailjob($job['gcj_id_job']);
                $mailer->send();
            } catch (\Zend_Mail_Exception $exception) {
                $fields = $mailer->getMailFields(false);

                $info = sprintf("Error mailing to %s respondent %s with email address %s.", $fields['organization'], $fields['full_name'], $fields['email']
                );

                // Use a gems exception to pass extra information to the log
                $gemsException = new \Gems_Exception($info, 0, $exception);
                \Gems_Log::getLogger()->logError($gemsException);

                return false;
            }
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
    protected function getFallbackEmail(array $job, $sendByMail, \Gems_Mail_TokenMailer $mailer)
    {
        // Set the from address to use in this job
        switch ($job['gcj_fallback_method']) {
            case 'O':   // Send on behalf of organization
                $organization = $mailer->getOrganization();
                return $organization->getContactName() . ' <' . $organization->getEmail() . '>';

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
                return $mailer->getOrganization()->getEmail();

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
     * @return string or null
     * @throws \Gems_Exception
     */
    protected function getFromName(array $job, $sendByMail, \Gems_Mail_TokenMailer $mailer)
    {
        // Set the from address to use in this job
        switch ($job['gcj_from_method']) {
            case 'O':   // Send on behalf of organization
                return $mailer->getOrganization()->getContactName();

            default:
                return null;
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

    protected function getTopic()
    {
        return 'mail';
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
