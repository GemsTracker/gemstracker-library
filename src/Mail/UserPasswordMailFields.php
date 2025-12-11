<?php

namespace Gems\Mail;

use Gems\User\UserPasswordResetException;

class UserPasswordMailFields extends UserMailFields
{
    public function getMailFields(string $language = null): array
    {
        $mailFields = parent::getMailFields();

        $mailFields += [
            'tfa_method' => $this->user->getTfaMethodDescription(),
            ];

        try {
            $mailFields += [
                'reset_key' => $this->user->getPasswordResetKey(),
                'reset_url' => $this->user->getBaseOrganization()->getLoginUrl() . '/index/resetpassword/key/' . $this->user->getPasswordResetKey(),
            ];
        } catch (UserPasswordResetException $e) {
            $mailFields += [
                'reset_key' => 'Password cannot be reset!',
                'reset_url' => $this->user->getBaseOrganization()->getLoginUrl(),
            ];
        }

        return $mailFields;
    }
}
