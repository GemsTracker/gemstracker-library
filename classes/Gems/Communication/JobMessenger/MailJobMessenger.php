<?php

namespace Gems\Communication\JobMessenger;


use Gems\Event\Application\TokenMailSent;
use Gems\Log\LogHelper;
use Gems\Communication\CommunicationRepository;
use Gems\Mail\ManualMailerFactory;
use Gems\Mail\TemplatedEmail;
use Gems\Mail\TokenMailFields;
use Gems\User\UserRepository;
use Laminas\Db\Adapter\Adapter;
use Mezzio\Template\TemplateRendererInterface;
use MUtil\Registry\TargetTrait;
use MUtil\Translate\TranslateableTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Mime\Address;

class MailJobMessenger extends JobMessengerAbstract implements \MUtil_Registry_TargetInterface
{
    use TargetTrait;
    use TranslateableTrait;

    /**
     * @var array config
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

    /**
     * @var ManualMailerFactory
     */
    protected $manualMailerFactory;

    /**
     * @var TemplateRendererInterface
     */
    protected $template;

    /**
     * @var UserRepository
     */
    protected $userRepository;

    public function sendCommunication(array $job, array $tokenData, $preview)
    {
        $mailRepository = new CommunicationRepository($this->db2, $this->config);
        $tracker = $this->loader->getTracker();
        $token = $tracker->getToken($tokenData);
        $tokenSelect = $tracker->getTokenSelect();

        $language = $mailRepository->getCommunicationLanguage($token->getRespondentLanguage());

        $mailFields = (new TokenMailFields($token, $this->config, $this->translate, $tokenSelect))->getMaiLFields($language);
        $mailTexts = $mailRepository->getCommunicationTexts($job['gcj_id_message'], $language);
        if ($mailTexts === null) {
            throw new \MailException('No template data found');
        }

        $email = new TemplatedEmail($this->template);
        $email->subject($mailTexts['subject'], $mailFields);

        $sendById = $job['gcj_id_user_as'];
        $to = $this->getToEmail($job, $token, $tokenData['can_email']);

        if (empty($to)) {
            if ($preview) {
                $this->addBatchMessage(sprintf(
                    $this->_('%s %s can not be sent because no email address is available.'), $token->getPatientNumber(), $token->getSurveyName()
                ));
            }
            // Skip to the next token now
            return null;
        }

        // If the email is sent to a fall back address, we need to change it!
        if ($token->getEmail() !== $to) {
            $email->addTo(new Address($to, $token->getRespondentName()));
        }

        // The variable from is used in the preview message
        $from = $this->getFromEmail($job, $token);
        $fromName = $this->getFromName($job, $token);

        $mailer = $this->manualMailerFactory->getMailer($from);

        if ($preview) {
            $this->addBatchMessage(sprintf(
                $this->_('Would be sent: %s %s to %s using %s as sender'), $token->getPatientNumber(), $token->getSurveyName(), $email, $from
            ));
        } else {
            try {
                $email->addFrom(new Address($from, $fromName));

                $email->htmlTemplate($mailRepository->getTemplate($token->getOrganization()), $mailTexts['body'], $mailFields);
                $mailer->send($email);

                $event = new TokenMailSent($email, $token, $this->currentUser, $job);
                $this->event->dispatch($event, $event::NAME);

            } catch (\Zend_Mail_Exception $exception) {
                $info = sprintf("Error mailing to %s respondent %s with email address %s.",
                    $mailFields['organization'],
                    $mailFields['full_name'],
                    $mailFields['email']
                );

                // Use a gems exception to pass extra information to the log
                $gemsException = new \Gems_Exception($info, 0, $exception);

                $this->logger->error(LogHelper::getMessageFromException($gemsException));

                return false;
            }
        }
    }

    /**
     *
     * @param array $job
     * @param \Gems_Tracker_Token $token
     * @return string or null
     * @throws \Gems_Exception
     */
    public function getFallbackEmail(array $job, \Gems_Tracker_Token $token)
    {
        // Set the from address to use in this job
        switch ($job['gcj_fallback_method']) {
            case 'O':   // Send on behalf of organization
                $organization = $token->getOrganization();
                return $organization->getEmail();

            case 'U':   // Send on behalf of fixed user
                return $this->getUserEmail((int)$job['gcj_id_user_as']);

            case 'F':   // Send on behalf of fixed email address
                return $job['gcj_fallback_fixed'];

            case 'S':   // Use site email
                if (isset($this->config['email']['site'])) {
                    return $this->config['email']['site'];
                }


            default:
                throw new \Gems_Exception(sprintf($this->_('Invalid option for `%s`'), $this->_('Fallback address used')));
        }
    }

    /**
     *
     * @param array $job
     * @param \Gems_Tracker_Token $token
     * @return string or null
     * @throws \Gems_Exception
     */
    public function getFromEmail(array $job, \Gems_Tracker_Token $token)
    {
        // Set the from address to use in this job
        switch ($job['gcj_from_method']) {
            case 'O':   // Send on behalf of organization
                return $token->getOrganization()->getEmail();

            case 'U':   // Send on behalf of fixed user
                return $this->getUserEmail((int)$job['gcj_id_user_as']);

            case 'F':   // Send on behalf of fixed email address
                return $job['gcj_from_fixed'];

            case 'S':   // Use site email
                if (isset($this->config['email']['site'])) {
                    return $this->config['email']['site'];
                }

            default:
                throw new \Gems_Exception(sprintf($this->_('Invalid option for `%s`'), $this->_('From address used')));
        }
    }

    /**
     *
     * @param array $job
     * @param \Gems_Tracker_Token $token
     * @return string or null
     * @throws \Gems_Exception
     */
    public function getFromName(array $job, \Gems_Tracker_Token $token)
    {
        // Set the from address to use in this job
        switch ($job['gcj_from_method']) {
            case 'O':   // Send on behalf of organization
                return $token->getOrganization()->getContactName();

            default:
                return null;
        }
    }

    /**
     *
     * @param array $job
     * @param \Gems_Tracker_Token $token
     * @param boolean $canBeMailed True when allowed to mail respondent
     * @return string or null
     * @throws \Gems_Exception
     */
    public function getToEmail(array $job, \Gems_Tracker_Token $token, $canBeMailed)
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
                return $this->getFallbackEmail($job, $token);

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
                return $this->getFallbackEmail($job, $token);

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
    protected function getUserEmail(int $userId): ?string
    {
        $this->userRepository->getEmailFromUserId($userId);
    }
}
