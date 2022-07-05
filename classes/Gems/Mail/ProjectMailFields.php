<?php

namespace Gems\Mail;

class ProjectMailFields implements MailFieldsInterface
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function getMailFields(): array
    {
        $mailFields = [
            'project' => null,
            'project_description' => null,
            'project_from' => null,
        ];

        if (isset($this->config['app']['name'])) {
            $mailFields['project'] = $this->config['app']['name'];
        }
        if (isset($this->config['app']['description'])) {
            $mailFields['project'] = $this->config['app']['description'];
        }
        if (isset($this->config['email']['site'])) {
            $mailFields['project_from'] = $this->config['email']['site'];
        }

        return $mailFields;
    }
}