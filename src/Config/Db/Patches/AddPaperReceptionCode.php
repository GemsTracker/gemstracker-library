<?php

declare(strict_types=1);

namespace Gems\Config\Db\Patches;

use Gems\Db\Migration\PatchAbstract;
use Gems\Db\ResultFetcher;

class AddPaperReceptionCode extends PatchAbstract
{
    public function __construct(
        private ResultFetcher $resultFetcher,
    )
    {
    }

    public function getDescription(): string|null
    {
        return 'Add paper reception code';
    }

    public function getOrder(): int
    {
        return 20260217000000;
    }

    public function up(): array
    {
        if ($this->resultFetcher->fetchOne('SELECT grc_id_reception_code FROM gems__reception_codes WHERE grc_id_reception_code = ?', ['paper'])) {
            return [];
        }

        return [
            "INSERT INTO `gems__reception_codes` (`grc_id_reception_code`, `grc_description`, `grc_success`, `grc_for_surveys`, `grc_changed_by`, `grc_created_by`) VALUES
                ('paper',	'Filled in on paper',	1,	1, 1, 1);"
        ];
    }
}