<?php

namespace Gems\Communication\JobMessenger;

use Gems\Communication\Http\SmsClientInterface;
use Gems\Event\Application\RespondentCommunicationSent;
use Gems\Event\Application\TokenCommunicationSent;
use Gems\Exception\ClientException;
use Gems\Log\LogHelper;
use Gems\Communication\CommunicationRepository;
use Gems\Mail\TokenMailFields;
use Gems\User\Filter\DutchPhonenumberFilter;
use Laminas\Db\Adapter\Adapter;
use League\HTMLToMarkdown\HtmlConverter;
use MUtil\Registry\TargetTrait;
use MUtil\Translate\TranslateableTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class SmsJobMessenger extends JobMessengerAbstract implements \MUtil_Registry_TargetInterface
{
    use TargetTrait;
    use TranslateableTrait;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var \Gems_User_User
     */
    protected $currentUser;

    /**
     * @var Adapter
     */
    protected $db2;

    /**
     * @var EventDispatcher
     */
    protected $event;

    /**
     * @var \Gems_Loader
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

    protected function getFrom(array $job, \Gems_Tracker_Token $token)
    {
        // Set the from address to use in this job
        switch ($job['gcj_from_method']) {
            case 'O':   // Send on behalf of organization
                return $token->getOrganization()->getSmsFrom();

            case 'F':   // Send on behalf of fixed email address
                return $job['gcj_from_fixed'];

            default:
                throw new \Gems_Exception(sprintf($this->_('Invalid option for `%s`'), $this->_('From address used')));
        }
    }

    protected function getMessage(array $job, array $tokenData)
    {
        $tracker = $this->loader->getTracker();
        $token = $tracker->getToken($tokenData);
        $tokenSelect = $tracker->getTokenSelect();
        $mailRepository = new CommunicationRepository($this->db2, $this->config);
        $language = $mailRepository->getCommunicationLanguage($token->getRespondentLanguage());


        $mailFields = (new TokenMailFields($token, $this->config, $this->translate, $tokenSelect))->getMaiLFields($language);
        $mailRepository = new CommunicationRepository($this->db2, $this->config);
        $mailTexts = $mailRepository->getCommunicationTexts($job['gcj_id_message'], $language);


        $twigLoader = new ArrayLoader([
            'message' => $mailTexts['body'],
        ]);

        $twig = new Environment($twigLoader, [
            'autoescape' => false,
        ]);

        $converter = new HtmlConverter([
            'hard_break' => true,
            'strip_tags' => true,
            'remove_nodes' => 'head style',
        ]);

        return $converter->convert($twig->render('message', $mailFields));
    }

    protected function getPhonenumber(array $job, \Gems_Tracker_Token $token, $canBeMessaged)
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
                throw new \Gems_Exception(sprintf($this->_('Invalid option for `%s`'), $this->_('Filler')));
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
                throw new \Gems_Exception(sprintf($this->_('Invalid option for `%s`'), $this->_('Addresses used')));
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
            throw new \Gems_Communication_Exception(sprintf('No Sms Client with id %s found', $clientId));
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

                $event = new TokenCommunicationSent($token, $this->currentUser, $job);
                $event->setFrom([$from]);
                $event->setSubject($job['gct_name']);
                $event->setTo([$filteredNumber]);
                $this->event->dispatch($event, $event::NAME);

            } catch (ClientException $e) {

                $info = sprintf("Error sending sms to %s respondent %s with email address %s.",
                    $token->getOrganizationId(),
                    $token->getRespondentId(),
                    $filteredNumber
                );

                // Use a gems exception to pass extra information to the log
                $gemsException = new \Gems_Exception($info, 0, $e);
                $this->logger->error(LogHelper::getMessageFromException($gemsException));

                return false;
            }
        }
    }
}
