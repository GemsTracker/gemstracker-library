<?php

namespace Gems\Mail;

class UserPasswordMailFields extends UserMailFields
{
    public function getMailFields(): array
    {
        $mailFields = parent::getMailFields();
        $mailFields += [
            'reset_key' => $this->user->getPasswordResetKey(),
            'reset_url' => $this->user->getBaseOrganization()->getLoginUrl() . '/index/resetpassword/key/' . $this->user->getPasswordResetKey(),
        ];

        return $mailFields;
    }
}