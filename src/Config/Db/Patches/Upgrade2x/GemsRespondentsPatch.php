<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\DatabaseInfo;
use Gems\Db\Migration\PatchAbstract;

class GemsRespondentsPatch extends PatchAbstract
{
    public function __construct(
        protected array $config,
        protected readonly DatabaseInfo $databaseInfo,
    )
    {}

    public function getDescription(): string|null
    {
        return 'Update gems__respondents for Gemstracker 2.x';
    }

    public function getOrder(): int
    {
        return 20230102000000;
    }

    public function up(): array
    {
        $statements[] = 'ALTER TABLE gems__respondents MODIFY COLUMN grs_id_user bigint unsigned NOT NULL';

        if ($this->databaseInfo->tableHasConstraint('gems__respondents', 'grs_bsn')) {
            $statements[] = 'ALTER TABLE gems__respondents DROP KEY grs_bsn';
        }
        if ($this->databaseInfo->tableHasConstraint('gems__respondents', 'grs_bsn')) {
            $statements[] = 'ALTER TABLE gems__respondents DROP KEY grs_bsn';
        }
        if (! $this->databaseInfo->tableHasConstraint('gems__respondents', 'grs_ssn')) {
            $statements[] = 'ALTER TABLE gems__respondents ADD UNIQUE KEY grs_ssn (grs_ssn)';
        }

        return $statements;
    }
}
