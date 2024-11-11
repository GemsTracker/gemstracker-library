<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\DatabaseInfo;
use Gems\Db\Migration\PatchAbstract;

class GemsReceptionCodesPatch extends PatchAbstract
{
    public function __construct(
        protected array $config,
        protected readonly DatabaseInfo $databaseInfo,
    )
    { }

    public function getDescription(): string|null
    {
        return 'Update gems__reception_codes for Gemstracker 2.x';
    }

    public function getOrder(): int
    {
        return 20230102000000;
    }

    public function up(): array
    {
        $statements = [
            "ALTER TABLE gems__reception_codes MODIFY COLUMN grc_for_surveys tinyint NOT NULL DEFAULT '0'",
            "ALTER TABLE gems__reception_codes MODIFY COLUMN grc_redo_survey tinyint NOT NULL DEFAULT '0'",
        ];

        if ($this->databaseInfo->tableHasConstraint('gems__reception_codes', 'grc_success_2')) {
            $statements[] = 'ALTER TABLE gems__reception_codes DROP KEY grc_success_2';
        }

        return $statements;
    }
}
