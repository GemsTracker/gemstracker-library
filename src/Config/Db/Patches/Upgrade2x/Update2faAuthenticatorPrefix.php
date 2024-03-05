<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;

class Update2faAuthenticatorPrefix extends PatchAbstract
{
    public function getDescription(): string|null
    {
        return 'Update gems__user_logins two factor keys to have AuthenticatorTotp instead of GoogleAuthenticator';
    }

    public function getOrder(): int
    {
        return 20240305120000;
    }

    public function up(): array
    {
        return [
            "UPDATE gems__user_logins SET gul_two_factor_key = REPLACE(gul_two_factor_key, 'GoogleAuthenticator', 'AuthenticatorTotp') WHERE gul_two_factor_key LIKE 'GoogleAuthenticator%'",
        ];
    }
}