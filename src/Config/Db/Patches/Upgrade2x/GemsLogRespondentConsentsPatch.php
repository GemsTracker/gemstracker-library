<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;
use Gems\Db\ResultFetcher;
use Laminas\Db\Adapter\Adapter;

class GemsLogRespondentConsentsPatch extends PatchAbstract
{
    var array $gems_table_constraints;

    public function __construct(
        protected array $config,
    )
    {
    }

    protected function prepare(): void
    {
        $db = new Adapter($this->config['db']);
        $resultFetcher = new ResultFetcher($db);
        $sql = sprintf('SELECT * FROM information_schema.table_constraints_extensions WHERE constraint_schema = "%s" AND table_name = "%s"', $this->config['db']['database'], 'gems__log_respondent_consents');
        $this->gems_table_constraints = $resultFetcher->fetchAll($sql) ?? [];
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
        $this->prepare();

        $statements = [];
        // Check if the key we wanty to drop exists.
        // If it does, we need to drop it.
        foreach ($this->gems_table_constraints as $constraint) {
            if ($constraint['CONSTRAINT_NAME'] === 'glrc_id_user') {
                $statements[] = 'ALTER TABLE gems__log_respondent_consents DROP KEY glrc_id_user';
                break;
            }
        }

        return $statements;
    }
}
