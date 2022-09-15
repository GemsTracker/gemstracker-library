<?php

namespace Gems\Mail;

use Gems\Tracker\Respondent;

class RespondentMailFields extends OrganizationMailFields
{
    private Respondent $respondent;

    public function __construct(Respondent $respondent, array $config)
    {
        $this->respondent = $respondent;
        parent::__construct($respondent->getOrganization(), $config);
    }

    public function getMailFields(string $language = null): array
    {
        $mailFields = parent::getMailFields();
        $mailFields += [
            'dear'          => $this->respondent->getDearGreeting($language),
            'email'         => $this->respondent->getEmailAddress(),
            'from'          => null,
            'first_name'    => $this->respondent->getFirstName(),
            'full_name'     => $this->respondent->getFullName(),
            'greeting'      => $this->respondent->getGreeting($language),
            'salutation'    => $this->respondent->getSalutation($language),
            'last_name'     => $this->respondent->getLastName(),
            'name'          => $this->respondent->getName(),
            'reply_to'      => null,
            'to'            => $this->respondent->getEmailAddress(),
            'reset_ask'     => null,
        ];

        return $mailFields;
    }
}