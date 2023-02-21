<?php

namespace Gems\Communication\JobMessenger;

use Gems\Communication\Exception;
use Gems\Event\Application\TokenEventMailFailed;
use Gems\Event\Application\TokenEventMailSent;
use Gems\Communication\CommunicationRepository;
use Gems\Legacy\CurrentUserRepository;
use Gems\Tracker;
use Gems\Tracker\Token;
use Gems\User\UserRepository;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;

class MailJobMessenger implements JobMessengerInterface
{
    public function __construct(
        protected Tracker $tracker,
        protected EventDispatcherInterface $event,
        protected CommunicationRepository $communicationRepository,
        protected UserRepository $userRepository,
        protected readonly array $config,
        protected CurrentUserRepository $currentUserRepository,
    )
    {
    }

    public function sendCommunication(array $job, Token $token, bool $preview): ?bool
    {
        $language = $this->communicationRepository->getCommunicationLanguage($token->getRespondentLanguage());

        $mailFields = $this->communicationRepository->getTokenMailFields($token, $language);
        $mailTexts = $this->communicationRepository->getCommunicationTexts($job['gcj_id_message'], $language);
        if ($mailTexts === null) {
            throw new \MailException('No template data found');
        }

        $email = $this->communicationRepository->getNewEmail();
        $email->subject($mailTexts['subject'], $mailFields);

        $to = $this->getToEmail($job, $token);

        if (empty($to)) {
            // Log: no to found
            return null;
        }

        // The variable from is used in the preview message
        $from = $this->getFromEmail($job, $token);
        $fromName = $this->getFromName($job, $token);
        if ($fromName === null) {
            $fromName = '';
        }

        $mailer = $this->communicationRepository->getMailer($from);

        $currentUserId = $this->currentUserRepository->getCurrentUserId();

        try {
            $email->addFrom(new Address($from, $fromName));
            $email->addTo(new Address($to, $token->getRespondentName()));

            $email->htmlTemplate($this->communicationRepository->getTemplate($token->getOrganization()), $mailTexts['body'], $mailFields);
            $mailer->send($email);

            $event = new TokenEventMailSent($email, $token, $currentUserId, $job);
            $this->event->dispatch($event, $event::NAME);

        } catch (TransportExceptionInterface  $exception) {

            $event = new TokenEventMailFailed($exception, $email, $token, $currentUserId, $job);
            $this->event->dispatch($event, $event::NAME);

            return false;
        }
        return true;
    }

    public function getFallbackEmail(array $job, Token $token): string
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
                throw new Exception('Invalid option for \'Fallback address used\'');
        }
    }

    public function getFromEmail(array $job, Token $token): string
    {
        // Set the from address to use in this job
        switch ($job['gcj_from_method']) {
            case 'O':   // Send on behalf of organization
                if (!$token->getOrganization()->hasEmail()) {
                    throw new Exception(sprintf('Organization %s has no E-mail for job %d ', $token->getOrganization()->getName(), $job['gcj_id_job']));
                }
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
                throw new Exception('Invalid option for \'From address used\'');
        }
    }

    public function getFromName(array $job, Token $token): ?string
    {
        // Set the from address to use in this job
        switch ($job['gcj_from_method']) {
            case 'O':   // Send on behalf of organization
                return $token->getOrganization()->getContactName();

            default:
                return null;
        }
    }

    public function getToEmail(array $job, Token $token): string
    {
        $email = null;

        switch ($job['gcj_target']) {
            case '0':
                if ($token->getRespondentTrack()->isMailable()) {
                    $email = $token->getEmail();
                }
                break;

            case '1':
                if($token->hasRelation() && $token->getRelation()->isMailable()) {
                    $email = $token->getRelation()->getEmail();
                }
                break;

            case '2':
                if ($token->getRespondent()->isMailable()) {
                    $email = $token->getRespondent()->getEmailAddress();
                }
                break;

            case '3':
                return $this->getFallbackEmail($job, $token);

            default:
                throw new Exception('Invalid option for \'Filler\'');
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
                throw new Exception('Invalid option for \'Addresses used\'');
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
        return $this->userRepository->getEmailFromUserId($userId);
    }
}
