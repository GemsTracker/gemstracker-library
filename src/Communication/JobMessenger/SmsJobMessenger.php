<?php

namespace Gems\Communication\JobMessenger;

use Gems\Communication\Http\SmsClientInterface;
use Gems\Communication\Http\SpryngSmsClient;
use Gems\Event\Application\TokenEventCommunicationFailed;
use Gems\Event\Application\TokenEventCommunicationSent;
use Gems\Exception\ClientException;
use Gems\Legacy\CurrentUserRepository;
use Gems\Communication\CommunicationRepository;
use Gems\Tracker;
use Gems\Tracker\Token;
use Gems\User\Filter\DutchPhonenumberFilter;
use League\HTMLToMarkdown\HtmlConverter;
use Psr\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Gems\Communication\Exception;
use Zalt\Base\TranslatorInterface;

class SmsJobMessenger implements JobMessengerInterface
{
    protected int $currentUserId;

    public function __construct(
        protected Tracker $tracker,
        protected EventDispatcherInterface $event,
        protected CommunicationRepository $communicationRepository,
        protected TranslatorInterface $translator,
        CurrentUserRepository $currentUserRepository,
    )
    {
        $this->currentUserId = $currentUserRepository->getCurrentUserId();
    }

    protected function getFallbackPhonenumber($job)
    {
        return $job['gcj_fallback_fixed'];
    }

    protected function getFrom(array $job, Token $token)
    {
        // Set the from address to use in this job
        switch ($job['gcj_from_method']) {
            case 'O':   // Send on behalf of organization
                return $token->getOrganization()->getSmsFrom();

            case 'F':   // Send on behalf of fixed email address
                return $job['gcj_from_fixed'];

            default:
                throw new Exception(sprintf($this->translator->_('Invalid option for `%s`'), $this->translator->_('From address used')));
        }
    }

    protected function getMessage(array $job, Token $token)
    {
        $language = $this->communicationRepository->getCommunicationLanguage($token->getRespondentLanguage());

        $mailFields = $this->communicationRepository->getTokenMailFields($token, $language);
        $mailTexts = $this->communicationRepository->getCommunicationTexts($job['gcj_id_message'], $language);

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

    protected function getPhoneNumber(array $job, Token $token, $canBeMessaged)
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
                throw new Exception('Invalid option for \'Filler\'');
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
                throw new Exception('Invalid option for \'Addresses used\'');
        }
    }

    public function sendCommunication(array $job, Token $token, bool $preview): ?bool
    {
        $clientId = $job['gcm_messenger_identifier'] ?? SmsClientInterface::class;
        $smsClient = $this->communicationRepository->getSmsClient($clientId);

        if (!($smsClient instanceof SmsClientInterface)) {
            throw new Exception(sprintf('No Sms Client with id %s found', $clientId));
        }

        $number = $this->getPhoneNumber($job, $token, true);
        $message = $this->getMessage($job, $token);
        $from = $this->getFrom($job, $token);
        $phoneNumberFilter = new DutchPhonenumberFilter();
        $filteredNumber = $phoneNumberFilter->filter($number);

        if (!$filteredNumber) {
            return false;
        }

        try {

            $smsClient->sendMessage($filteredNumber, $message, $from);

            $event = new TokenEventCommunicationSent($token, $this->currentUserId, $job);
            $event->setFrom([$from]);
            $event->setSubject($job['gct_name']);
            $event->setTo([$filteredNumber]);
            $this->event->dispatch($event);

        } catch (ClientException $exception) {

            $smsClient->sendMessage($filteredNumber, $message, $from);

            $event = new TokenEventCommunicationFailed($exception, $token, $this->currentUserId, $job);
            $event->setFrom([$from]);
            $event->setSubject($job['gct_name']);
            $event->setTo([$filteredNumber]);
            $this->event->dispatch($event);

            return false;
        }

        return true;
    }


}
