<?php

namespace Gems\Repository;

use Gems\Communication\CommunicationRepository;
use Gems\Communication\Exception;
use Gems\Event\Application\TokenEventMailFailed;
use Gems\Event\Application\TokenEventMailSent;
use Gems\Exception\MailException;
use Gems\Legacy\CurrentUserRepository;
use Gems\Tracker\Token;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class CommRepository
{
    public ?TransportExceptionInterface $lastException = null;

    public function __construct(
        protected readonly CommunicationRepository $communicationRepository,
        protected readonly CurrentUserRepository $currentUserRepository,
        protected readonly EventDispatcherInterface $event,
    )
    {

    }

    public function sendTokenEmail(
        Token $token,
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
        $mailTemplate = $this->communicationRepository->getTemplate($token->getOrganization());

        $job = [
            'gcj_id_message' => $templateId,
        ];

        $currentUserId = $this->currentUserRepository->getCurrentUserId();

        $email = $this->getEmail(
            $templateId,
            $language,
            $from,
            $fromName,
            [$to => $token->getRespondentName()],
            $subject,
            $body,
            $mailFields,
            $mailTemplate,
            'token',
        );

        $mailer = $this->communicationRepository->getMailer();

        try {
            $mailer->send($email);
        } catch(TransportExceptionInterface $exception) {
            $this->lastException = $exception;

            // Make sure this error does not disappear into nowhere
            error_log($exception->getMessage());
            // error_log($exception->getTraceAsString());

            $event = new TokenEventMailFailed($exception, $email, $token, $currentUserId, $job);
            $this->event->dispatch($event, $event::NAME);

            return false;
        }

        $event = new TokenEventMailSent($email, $token, $currentUserId, $job);
        $this->event->dispatch($event);
        return true;
    }

    protected function getEmail(
        int $templateId = null,
        string $language = 'en',
        string $from = null,
        string $fromName = '',
        Address|string|array|null $to = null,
        string $subject = null,
        string $body = null,
        array $mailFields = [],
        string $mailTemplate = 'mail::gems',
        string|null $type = null,
    ): Email
    {
        $mailTexts = null;
        if ($subject !== null && $body !== null) {
            if ($type !== null) {
                $body = $this->communicationRepository->filterRawVariables($body, $type);
            }
            $mailTexts = [
                'subject' => $subject,
                'body' => $body,
            ];
        } elseif ($templateId !== null) {
            $mailTexts = $this->communicationRepository->getCommunicationTexts($templateId, $language);
        }

        if ($mailTexts === null) {
            throw new MailException('No template data found');
        }

        $email = $this->communicationRepository->getNewEmail();
        $email->subject($mailTexts['subject'], $mailFields);

        $email->addFrom(new Address($from, $fromName));

        foreach((array)$to as $toKey => $toValue) {
            if ($toValue === null) {
                continue;
            }
            if (is_int($toKey)) {
                if (!$toValue instanceof Address) {
                    $toValue = new Address($toValue);
                }
                $email->addTo($toValue);
                continue;
            }
            $email->addTo(new Address($toKey, $toValue));
        }

        $email->htmlTemplate($mailTemplate, $mailTexts['body'], $mailFields);

        return $email;
    }
}