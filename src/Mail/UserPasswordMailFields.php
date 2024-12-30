<?php

namespace Gems\Mail;

class UserPasswordMailFields extends UserMailFields
{
    public function getMailFields(string $language = null): array
    {
        $mailFields = parent::getMailFields();
        $mailFields += [
            'tfa_method' => $this->user->getTfaMethodDescription(),
            'reset_key' => $this->user->getPasswordResetKey(),
            'reset_url' => $this->user->getBaseOrganization()->getLoginUrl() . '/index/resetpassword/key/' . $this->user->getPasswordResetKey(),
        ];

        return $mailFields;
    }
}