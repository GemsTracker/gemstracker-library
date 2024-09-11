<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\DatabaseInfo;
use Gems\Db\Migration\PatchAbstract;

class GemsLogSetupPatch extends PatchAbstract
{
    public function __construct(
        protected array $config,
        protected readonly DatabaseInfo $databaseInfo,
    )
    { }

    public function getDescription(): string|null
    {
        return 'Update gems__log_setup for Gemstracker 2.x';
    }

    public function getOrder(): int
    {
        return 20230102000000;
    }

    public function up(): array
    {
        $statements = [];

        if ($this->databaseInfo->tableHasConstraint('gems__log_setup', 'gls_name_2')) {
            $statements[] = 'ALTER TABLE gems__log_setup DROP KEY gls_name_2';
        }
        if ($this->databaseInfo->tableHasConstraint('gems__log_setup', 'gls_name_3')) {
            $statements[] = 'ALTER TABLE gems__log_setup DROP KEY gls_name_3';
        }

        return $statements;
    }
}
