<?php

namespace Gems\Config\Db\Patches\Upgrade2x;

use Gems\Db\Migration\PatchAbstract;
use Gems\Db\ResultFetcher;
use Laminas\Db\Adapter\Adapter;

class GemsRespondentRelationsFirstPatch extends PatchAbstract
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
        $sql = sprintf('SELECT * FROM information_schema.table_constraints_extensions WHERE constraint_schema = "%s" AND table_name = "%s"', $this->config['db']['database'], 'gems__respondent_relations');
        $this->gems_table_constraints = $resultFetcher->fetchAll($sql);
    }

    public function getDescription(): string|null
    {
        return 'Update gems__respondent_relations for Gemstracker 2.x';
    }

    public function getOrder(): int
    {
        return 20230102000101;
    }

    public function up(): array
    {
        $this->prepare();

        $statements = [
            'ALTER TABLE gems__respondent_relations MODIFY COLUMN grr_comments TEXT',
        ];
        // Check if the keys we want to drop exist.
        // If they do, we need to drop them.
        foreach ($this->gems_table_constraints as $constraint) {
            if ($constraint['CONSTRAINT_NAME'] === 'grr_id_respondent_staff') {
                $statements[] = 'ALTER TABLE gems__respondent_relations DROP KEY grr_id_respondent_staff';
                continue;
            }
            if ($constraint['CONSTRAINT_NAME'] === 'grr_id_respondent') {
                $statements[] = 'ALTER TABLE gems__respondent_relations DROP KEY grr_id_respondent';
                continue;
            }
        }

        return $statements;
    }
}
