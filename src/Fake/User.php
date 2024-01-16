<?php

namespace Gems\Fake;

use Gems\User\StaffUserDefinition;

class User extends \Gems\User\User
{
    public function __construct()
    {
        $data = $this->getUserData();

        $userDefinition = new StaffUserDefinition();
        parent::__construct($data, $userDefinition);
    }

    public function getBaseOrganization()
    {
        return new Organization();
    }

    protected function getUserData(): array
    {
        return [
            'user_id' => 1,
            'user_login' => 'jdevries',
            'user_last_name' => 'Vries',
            'user_surname_prefix' => 'de',
            'user_first_name' => 'Jip',
            'user_gender' => 'M',
            'user_email' => 'j.de.vries@example.test',
            'user_role'  => null,
        ];
    }

    public function getPasswordResetKey()
    {
        return hash('sha256', random_bytes(64));
    }

    public function setRole(?string $role)
    {
        $this->_setVar('user_role', $role);
    }
}