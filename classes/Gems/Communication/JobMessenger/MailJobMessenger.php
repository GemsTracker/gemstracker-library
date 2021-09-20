<?php

namespace Gems\Communication\JobMessenger;


use MUtil\Registry\TargetTrait;
use MUtil\Translate\TranslateableTrait;

class MailJobMessenger extends JobMessengerAbstract implements \MUtil_Registry_TargetInterface
{
    use TargetTrait;
    use TranslateableTrait;

    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * @var \Gems_Loader
     */
    protected $loader;

    public function sendCommunication(array $job, array $tokenData, $preview)
    {
        $mailLoader = $this->loader->getMailLoader();
        $mailer     = $mailLoader->getMailer('token', $tokenData['gto_id_token']);
        /* @var $mailer \Gems_Mail_TokenMailer */
        $token  = $mailer->getToken();

        $sendById = $job['gcj_id_user_as'];
        $email    = $this->getToEmail($job, $mailer, $token, $tokenData['can_email']);

        if (empty($email)) {
            if ($preview) {
                $this->addBatchMessage(sprintf(
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
        $from = $this->getFromEmail($job, $mailer);
        $fromName = $this->getFromName($job, $mailer);

        if ($preview) {
            $this->addBatchMessage(sprintf(
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
     * @param \Gems_Mail_TokenMailer $mailer
     * @return string or null
     * @throws \Gems_Exception
     */
    public function getFallbackEmail(array $job, \Gems_Mail_TokenMailer $mailer)
    {
        // Set the from address to use in this job
        switch ($job['gcj_fallback_method']) {
            case 'O':   // Send on behalf of organization
                $organization = $mailer->getOrganization();
                return $organization->getEmail();

            case 'U':   // Send on behalf of fixed user
                return $this->getUserEmail($job['gcj_id_user_as']);

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
     * @param \Gems_Mail_TokenMailer $mailer
     * @return string or null
     * @throws \Gems_Exception
     */
    public function getFromEmail(array $job, \Gems_Mail_TokenMailer $mailer)
    {
        // Set the from address to use in this job
        switch ($job['gcj_from_method']) {
            case 'O':   // Send on behalf of organization
                return $mailer->getOrganization()->getEmail();

            case 'U':   // Send on behalf of fixed user
                return $this->getUserEmail($job['gcj_id_user_as']);

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
     * @param \Gems_Mail_TokenMailer $mailer
     * @return string or null
     * @throws \Gems_Exception
     */
    public function getFromName(array $job, \Gems_Mail_TokenMailer $mailer)
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
     * @param \Gems_Mail_TokenMailer $mailer
     * @param \Gems_Tracker_Token $token
     * @param boolean $canBeMailed True when allowed to mail respondent
     * @return string or null
     * @throws \Gems_Exception
     */
    public function getToEmail(array $job, \Gems_Mail_TokenMailer $mailer, \Gems_Tracker_Token $token, $canBeMailed)
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
                return $this->getFallbackEmail($job, $mailer);

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
                return $this->getFallbackEmail($job, $mailer);

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
