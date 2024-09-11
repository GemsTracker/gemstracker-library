<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\DatabaseInfo;
use Gems\Db\Migration\PatchAbstract;
use Gems\Db\ResultFetcher;
use Laminas\Db\Adapter\Adapter;

class GemsUserPasswordsPatch extends PatchAbstract
{
    public function __construct(
        protected array $config,
        protected readonly DatabaseInfo $databaseInfo,
    )
    { }

    public function getDescription(): string|null
    {
        return 'Update gems__user_passwords for Gemstracker 2.x';
    }

    public function getOrder(): int
    {
        return 20230102000000;
    }

    public function up(): array
    {
        $statements = [];
        if ($this->databaseInfo->tableHasConstraint('gems__user_passwords', 'gup_reset_key_2')) {
            $statements[] = 'ALTER TABLE gems__user_passwords DROP KEY gup_reset_key_2';
        }

        return $statements;
    }
}
