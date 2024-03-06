<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;

class GemsLocationSeparatorPatch extends PatchAbstract
{
    public function getDescription(): string|null
    {
        return 'Change the organization separator in locations from : to |';
    }

    public function getOrder(): int
    {
        return 20240306160000;
    }

    public function up(): array
    {
        return [
            'UPDATE gems__locations SET glo_organizations = REPLACE(glo_organizations, ":", "|")',
        ];
    }

    public function down(): array
    {
        return [
            'UPDATE gems__locations SET glo_organizations = REPLACE(glo_organizations, "|", ":")',
        ];
    }
}