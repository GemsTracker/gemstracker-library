<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsTokenReplacements extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__token_replacements', [
                'id' => false,
                'primary_key' => ['gtrp_id_token_new'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gtrp_id_token_new', 'string', [
                'null' => false,
                'limit' => 9,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ])
            ->addColumn('gtrp_id_token_old', 'string', [
                'null' => false,
                'limit' => 9,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtrp_id_token_new',
            ])
            ->addColumn('gtrp_created', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'after' => 'gtrp_id_token_old',
            ])
            ->addColumn('gtrp_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gtrp_created',
            ])
            ->addIndex(['gtrp_id_token_old'], [
                'name' => 'gtrp_id_token_old',
                'unique' => false,
            ])
            ->create();
    }
}