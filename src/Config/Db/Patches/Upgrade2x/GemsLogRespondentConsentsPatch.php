<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\DatabaseInfo;
use Gems\Db\Migration\PatchAbstract;

class GemsLogRespondentConsentsPatch extends PatchAbstract
{
    public function __construct(
        protected array $config,
        protected readonly DatabaseInfo $databaseInfo,
    )
    {
    }

    public function getDescription(): string|null
    {
        return 'Update gems__log_respondent_consents for Gemstracker 2.x';
    }

    public function getOrder(): int
    {
        return 20230102000000;
    }

    public function up(): array
    {
        $statements = [];
        if ($this->databaseInfo->tableHasConstraint('gems__log_respondent_consents', 'glrc_id_user')) {
            $statements[] = 'ALTER TABLE gems__log_respondent_consents DROP KEY glrc_id_user';
        }
        return $statements;
    }
}
