<?php

namespace Gems\Mail;

use Gems\User\Organization;

class OrganizationMailFields extends ProjectMailFields
{
    private Organization $organization;

    public function __construct(Organization $organization, array $config)
    {
        parent::__construct($config);
        $this->organization = $organization;
    }

    public function getMailFields(): array
    {
        $mailFields = parent::getMailFields();
        $mailFields += [
            'organization'                 => $this->organization->getName(),
            'organization_location'        => $this->organization->getLocation(),
            'organization_login_url'       => $this->organization->getLoginUrl(),
            'organization_reply_name'      => $this->organization->getContactName(),
            'organization_reply_to'        => $this->organization->getEmail(),
            'organization_signature'       => nl2br($this->organization->getSignature()),
            'organization_unsubscribe_url' => $this->organization->getUnsubscribeUrl(),
            'organization_url'             => $this->organization->getUrl(),
            'organization_welcome'         => nl2br($this->organization->getWelcome()),
        ];

        return $mailFields;
    }

    public static function getRawFields(): array
    {
        $rawFields = parent::getRawFields();
        $rawFields[] = 'organization_signature';
        $rawFields[] = 'organization_welcome';
        return $rawFields;
    }
}