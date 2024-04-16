<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;

class GemsSystemUserEncryptionPatch extends PatchAbstract
{
    public function getDescription(): string|null
    {
        return 'Update default encryption of System user secrets';
    }

    public function getOrder(): int
    {
        return 20240416140000;
    }

    public function up(): array
    {
        return [
            'UPDATE gems__systemuser_setup SET gsus_secret_key = REPLACE(gsus_secret_key, \':v01:\', \':0:\') WHERE gsus_secret_key LIKE \':v01:%\'',
        ];
    }
}