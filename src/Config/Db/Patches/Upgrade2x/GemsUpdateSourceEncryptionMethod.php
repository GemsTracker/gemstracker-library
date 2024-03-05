<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;

class GemsUpdateSourceEncryptionMethod extends PatchAbstract
{
    public function getDescription(): string|null
    {
        return 'Update encryption method name prefix of gems__sources ls passwords';
    }

    public function getOrder(): int
    {
        return 20240305130000;
    }

    public function up(): array
    {
        return [
            "UPDATE gems__sources SET gso_ls_password = REPLACE(gso_ls_password, ':v01:', ':0:')",
        ];
    }
}