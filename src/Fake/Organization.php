<?php

namespace Gems\Fake;

class Organization extends \Gems\User\Organization
{
    public function __construct(array $sites, ?array $data = null)
    {
        parent::__construct(0, $sites, []);
        if ($data !== null) {
            $this->_data = $data;
            return;
        }
        $this->_data = $this->loadData(0);
    }

    protected function loadData($id)
    {
        return [
            'gor_id_organization' => $id,
            'gor_name' => 'Example organization',
            'preferredSite' => 'https://gemstracker.example.test',
            'gor_url' => 'https://www.example.test',
            'gor_contact_name' => 'Example',
            'gor_contact_email' => 'example@example.nl',
            'gor_contact_sms_from' => 'example',
            'gor_location' => 'Amsterdam',
            'gor_signature' => 'Kind regards, Example',
            'gor_welcome' => 'Welcome to Example',
            'gor_respondent_unsubscribe' => 'Gems\\Screens\\Respondent\\Unsubscribe\\EmailOnlyUnsubscribe',
        ];
    }
}