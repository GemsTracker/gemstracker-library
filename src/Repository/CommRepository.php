<?php

namespace Gems\Repository;

use Gems\Communication\CommunicationRepository;
use Gems\Event\Application\TokenEventMailFailed;
use Gems\Event\Application\TokenEventMailSent;
use Gems\Legacy\CurrentUserRepository;
use Gems\Tracker\Token;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;

class CommRepository
{
    public function __construct(
        protected CommunicationRepository $communicationRepository,
        protected CurrentUserRepository $currentUserRepository,
        protected EventDispatcherInterface $event,
    )
    {

    }

    public function sendEmail(Token $token,
        int $templateId = null,
        string $from = null,
        string $fromName = null,
        string $to = null,
        string $subject = null,
        string $body = null,
    ): bool
    {
        $language = $this->communicationRepository->getCommunicationLanguage($token->getRespondentLanguage());
        $mailFields = $this->communicationRepository->getTokenMailFields($token, $language);

        $mailTexts = null;
        if ($subject !== null && $body !== null) {
            $mailTexts = [
                'subject' => $subject,
                'body' => $body,
            ];
        } elseif ($templateId !== null) {
            $mailTexts = $this->communicationRepository->getCommunicationTexts($templateId, $language);
        }

        if ($mailTexts === null) {
            throw new \MailException('No template data found');
        }

        $mailer = $this->communicationRepository->getMailer();

        $currentUserId = $this->currentUserRepository->getCurrentUserId();

        $email = $this->communicationRepository->getNewEmail();
        $email->subject($mailTexts['subject'], $mailFields);

        $job = [
            'gcj_id_message' => $templateId,
        ];

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
}