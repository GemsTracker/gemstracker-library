<?php

namespace Gems\Mail;

use Gems\User\User;

class UserMailFields extends OrganizationMailFields
{
    protected User $user;

    public function __construct(User $user, array $config)
    {
        $this->user = $user;
        parent::__construct($user->getBaseOrganization(), $config);
    }

    public function getMailFields(string $language = null): array
    {
        $mailFields = parent::getMailFields();
        $organizationLoginUrl = $mailFields['organization_login_url'];

        $mailFields += [
            'dear'           => $this->user->getDearGreeting($language),
            'email'          => $this->user->getEmailAddress(),
            'first_name'     => $this->user->getFirstName(),
            'from'           => $this->user->getFrom(),
            'full_name'      => trim($this->user->getGenderHello($language) . ' ' . $this->user->getFullName()),
            'greeting'       => $this->user->getGreeting($language),
            'last_name'      => ltrim($this->user->getSurnamePrefix() . ' ') . $this->user->getLastName(),
            'login_url'      => $organizationLoginUrl,
            'name'           => $this->user->getFullName(),
            'login_name'     => $this->user->getLoginName(),
            'reset_ask'      => $organizationLoginUrl . '/index/resetpassword',
            'reset_in_hours' => $this->user->getPasswordResetKeyDuration(),
        ];

        $mailFields['reply_to']       = $mailFields['from'];
        $mailFields['to']             = $mailFields['email'];

        $mailFields['login_url']      = $mailFields['organization_login_url'];

        return $mailFields;
    }
}