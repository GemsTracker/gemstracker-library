<?php

namespace Gems\Mail;

use Gems\Exception;
use Gems\Legacy\CurrentUserRepository;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Mailer\EventListener\EnvelopeListener;

class MailBouncer
{

    protected ?array $recipients = null;

    protected ?string $sender = null;
    public function __construct(
        protected EventDispatcher $eventDispatcher,
        protected CurrentUserRepository $currentUserRepository,
        array $config)
    {
        if (isset($config['email'])) {
            $this->recipients = $this->getRecipientsFromConfig($config);
            $this->sender = $this->getSenderFromConfig($config);
        }

        $this->enable();
    }

    public function disable(): void
    {
        $this->eventDispatcher->removeSubscriber(new EnvelopeListener());
    }

    public function enable(): void
    {
        if ($this->recipients !== null || $this->sender !== null) {
            $this->eventDispatcher->addSubscriber(new EnvelopeListener($this->sender, $this->recipients));
        }
    }

    public function getRecipients(): ?array
    {
        return $this->recipients;
    }

    protected function getRecipientsFromConfig(array $config): ?array
    {
        if (isset($config['email']['to'])) {
            $recipients = explode(';', $config['to']);
            if (count($recipients)) {
                return $this->recipients;
            }
        }

        if (isset($config['email']['bounce']) && $config['email']['bounce'] === true) {
            $currentUser = $this->currentUserRepository->getCurrentUser();
            if ($currentUser) {
                $email = $currentUser->getEmailAddress();
                if ($email) {
                    return [$email];
                }
            }
            $currentOrganizationEmail = $this->currentUserRepository->getCurrentOrganization()->getEmail();
            if ($currentOrganizationEmail) {
                return [$currentOrganizationEmail];
            }
            if (isset($config['email']['site'])) {
                return [$config['email']['site']];
            }
            throw new Exception('Bounce is enabled, but no fallback e-mail address was found');
        }

        return null;
    }

    public function getSender(): ?string
    {
        return $this->sender;
    }

    protected function getSenderFromConfig(array $config): ?string
    {
        if (isset($config['email']['from'])) {
            return $config['email']['from'];
        }

        return null;
    }

    public function isActive(): bool
    {
        return $this->recipients !== null;
    }
}