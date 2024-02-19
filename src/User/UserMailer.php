<?php

namespace Gems\User;

use Gems\Communication\CommunicationRepository;
use Gems\Communication\Exception;
use Symfony\Component\Mime\Address;
use Zalt\Base\TranslatorInterface;

class UserMailer
{
    public function __construct(
        protected readonly CommunicationRepository $communicationRepository,
        protected readonly TranslatorInterface $translator,
        protected readonly array $config,
    )
    {
    }

    protected function getFrom(User $user): string
    {
        // Gather possible sources of a from address
        $sources[] = $user->getBaseOrganization();
        if ($user->getBaseOrganizationId() != $user->getCurrentOrganizationId()) {
            $sources[] = $user->getCurrentOrganization();
        }

        foreach ($sources as $source) {
            if ($from = $source->getFrom()) {
                return $from;
            }
        }

        if (isset($this->config['email']['site'])) {
            return $this->config['email']['site'];
        }

        // We really don't like it, but sometimes the only way to get a from address.
        return $user->getEmailAddress();
    }


    public function sendMail(User $user, string $subjectTemplate, string $bodyTemplate, bool $useResetFields = false, string|null $locale = null): void
    {
        if ($useResetFields && (! $user->canResetPassword())) {
            throw new Exception('Trying to send a password reset to a user that cannot be reset.');
        }

        $email = $this->communicationRepository->getNewEmail();
        $email->addTo(new Address($user->getEmailAddress(), $user->getFullName()));
        if (isset($this->config['email']['bcc'])) {
            $email->addBcc($this->config['email']['bcc']);
        }

        if ($useResetFields) {
            $fields = $this->communicationRepository->getUserPasswordMailFields($user, $locale);
        } else {
            $fields = $this->communicationRepository->getUserMailFields($user, $locale);
        }

        $email->htmlTemplate($this->communicationRepository->getTemplate($user->getBaseOrganization()), $bodyTemplate, $fields);

        $email->from($this->getFrom($user));
        $email->subject($subjectTemplate, $fields);

        $mailer = $this->communicationRepository->getMailer();

        try {
            $mailer->send($email);
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}