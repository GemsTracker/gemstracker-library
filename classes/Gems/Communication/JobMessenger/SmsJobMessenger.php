<?php

namespace Gems\Communication\JobMessenger;


use Gems\Batch\BatchHandlerTrait;
use Gems\Communication\Http\SmsClientInterface;
use Gems\Exception\ClientException;
use Gems\Log\LogHelper;
use Gems\User\Filter\DutchPhonenumberFilter;
use MUtil\Registry\TargetTrait;
use MUtil\Translate\TranslateableTrait;
use Psr\Log\LoggerInterface;

class SmsJobMessenger extends JobMessengerAbstract implements \MUtil\Registry\TargetInterface
{
    use TargetTrait;
    use TranslateableTrait;

    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    protected function getFallbackPhonenumber($job)
    {
        return $job['gcj_fallback_fixed'];
    }

    protected function getFrom(array $job, \Gems\Tracker\Token $token)
    {
        // Set the from address to use in this job
        switch ($job['gcj_from_method']) {
            case 'O':   // Send on behalf of organization
                return $token->getOrganization()->getSmsFrom();

            case 'F':   // Send on behalf of fixed email address
                return $job['gcj_from_fixed'];

            default:
                throw new \Gems\Exception(sprintf($this->_('Invalid option for `%s`'), $this->_('From address used')));
        }
    }

    protected function getMessage(array $job, array $tokenData)
    {
        $mailLoader = $this->loader->getMailLoader();
        $mailer       = $mailLoader->getMailer('token', $tokenData['gto_id_token']);
        $mailer->setTemplate($job['gcj_id_message']);

        return $mailer->getBodyText();
    }

    protected function getPhonenumber(array $job, \Gems\Tracker\Token $token, $canBeMessaged)
    {
        $phoneNumber = null;

        switch ($job['gcj_target']) {
            case '0':
                if ($canBeMessaged) {
                    $phoneNumber = $token->getPhonenumber();
                }
                break;

            case '1':
                if($canBeMessaged && $token->hasRelation()) {
                    $phoneNumber = $token->getRelation()->getPhonenumber();
                }
                break;

            case '2':
                if ($canBeMessaged) {
                    $phoneNumber = $token->getRespondent()->getPhonenumber();
                }
                break;

            case '3':
                return $this->getFallbackPhonenumber($job);

            default:
                throw new \Gems\Exception(sprintf($this->_('Invalid option for `%s`'), $this->_('Filler')));
        }


        switch ($job['gcj_to_method']) {
            case 'A':
                return $phoneNumber;

            case 'O':
                if ($phoneNumber) {
                    return $phoneNumber;
                }
            // Intentional fall through
            case 'F':
                return $this->getFallbackPhonenumber($job);

            default:
                throw new \Gems\Exception(sprintf($this->_('Invalid option for `%s`'), $this->_('Addresses used')));
        }
    }

    public function sendCommunication(array $job, array $tokenData, $preview)
    {
        $token = $this->loader->getTracker()->getToken($tokenData);
        $clientId = 'sms';
        if (isset($job['gcm_method_identifier'])) {
            $clientId = $job['gcm_method_identifier'];
        }
        $communicationLoader = $this->loader->getCommunicationLoader();
        $smsClient = $communicationLoader->getSmsClient($clientId);

        if (!($smsClient instanceof SmsClientInterface)) {
            throw new \Gems\Communication\Exception(sprintf('No Sms Client with id %s found', $clientId));
        }

        $number = $this->getPhoneNumber($job, $token, $tokenData['can_email']);
        $message = $this->getMessage($job, $tokenData);
        $from = $this->getFrom($job, $token);
        $phoneNumberFilter = new DutchPhonenumberFilter();
        $filteredNumber = $phoneNumberFilter->filter($number);

        if ($preview) {
            $this->addBatchMessage(sprintf(
                $this->_('Would be sent: %s %s to %s using %s as sender'), $token->getPatientNumber(), $token->getSurveyName(), $number, $from
            ));
        } else {
            try {

                $smsClient->sendMessage($filteredNumber, $message, $from);
                $token->setMessageSent();
                $this->logRespondentCommunication($token, $job, $number, $from);
            } catch (ClientException $e) {

                $info = sprintf("Error sending sms to %s respondent %s with email address %s.",
                    $token->getOrganizationId(),
                    $token->getRespondentId(),
                    $filteredNumber
                );

                // Use a gems exception to pass extra information to the log
                $gemsException = new \Gems\Exception($info, 0, $e);
                $this->logger->error(LogHelper::getMessageFromException($gemsException));

                return false;
            }
        }
    }

    /**
     * Log the communication for the respondent.
     */
    protected function logRespondentCommunication(\Gems\Tracker\Token $token, $job, $number, $from)
    {
        $respondent = $token->getRespondent();

        $currentUserId                = $this->loader->getCurrentUser()->getUserId();
        $changeDate                   = new \MUtil\Db\Expr\CurrentTimestamp();

        $logData['grco_id_to']        = $respondent->getId();

        $by = $job['gcj_id_user_as'];

        if (!$by) {
            $by = $currentUserId;
        }

        $logData['grco_id_by']        = $by;
        $logData['grco_organization'] = $token->getOrganizationId();
        $logData['grco_id_token']     = $token->getTokenId();

        $logData['grco_method']       = 'sms';
        $logData['grco_topic']        = substr($job['gct_name'], 0, 120);

        $logData['grco_address']      = $number;
        $logData['grco_sender']       = $from;

        $logData['grco_id_message']   = $job['gct_id_template'];
        $logData['grco_id_job']       = $job['gcj_id_job'];

        $logData['grco_changed']      = $changeDate;
        $logData['grco_changed_by']   = $currentUserId;
        $logData['grco_created']      = $changeDate;
        $logData['grco_created_by']   = $currentUserId;

        $this->db->insert('gems__log_respondent_communications', $logData);
    }
}
