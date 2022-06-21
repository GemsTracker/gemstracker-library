<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsCommTemplates extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__comm_templates', [
                'id' => false,
                'primary_key' => ['gct_id_template'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gct_id_template', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('gct_name', 'string', [
                'null' => false,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gct_id_template',
            ])
            ->addColumn('gct_target', 'string', [
                'null' => false,
                'limit' => 32,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gct_name',
            ])
            ->addColumn('gct_code', 'string', [
                'null' => true,
                'limit' => 64,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gct_target',
            ])
            ->addColumn('gct_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gct_code',
            ])
            ->addColumn('gct_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gct_changed',
            ])
            ->addColumn('gct_created', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'after' => 'gct_changed_by',
            ])
            ->addColumn('gct_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gct_created',
            ])
            ->addIndex(['gct_name'], [
                'name' => 'gct_name',
                'unique' => true,
            ])
            ->create();
    }
}