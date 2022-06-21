<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsSources extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__sources', [
                'id' => false,
                'primary_key' => ['gso_id_source'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8',
                'collation' => 'utf8_general_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gso_id_source', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('gso_source_name', 'string', [
                'null' => false,
                'limit' => 40,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gso_id_source',
            ])
            ->addColumn('gso_ls_url', 'string', [
                'null' => false,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gso_source_name',
            ])
            ->addColumn('gso_ls_class', 'string', [
                'null' => false,
                'default' => 'Gems_Source_LimeSurvey1m9Database',
                'limit' => 60,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gso_ls_url',
            ])
            ->addColumn('gso_ls_adapter', 'string', [
                'null' => true,
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gso_ls_class',
            ])
            ->addColumn('gso_ls_dbhost', 'string', [
                'null' => true,
                'limit' => 127,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gso_ls_adapter',
            ])
            ->addColumn('gso_ls_database', 'string', [
                'null' => true,
                'limit' => 127,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gso_ls_dbhost',
            ])
            ->addColumn('gso_ls_dbport', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_MEDIUM,
                'after' => 'gso_ls_database',
            ])
            ->addColumn('gso_ls_table_prefix', 'string', [
                'null' => true,
                'limit' => 127,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gso_ls_dbport',
            ])
            ->addColumn('gso_ls_username', 'string', [
                'null' => true,
                'limit' => 64,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gso_ls_table_prefix',
            ])
            ->addColumn('gso_ls_password', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gso_ls_username',
            ])
            ->addColumn('gso_encryption', 'string', [
                'null' => true,
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gso_ls_password',
            ])
            ->addColumn('gso_ls_charset', 'string', [
                'null' => true,
                'limit' => 8,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gso_encryption',
            ])
            ->addColumn('gso_active', 'boolean', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gso_ls_charset',
            ])
            ->addColumn('gso_status', 'string', [
                'null' => true,
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gso_active',
            ])
            ->addColumn('gso_last_synch', 'timestamp', [
                'null' => true,
                'after' => 'gso_status',
            ])
            ->addColumn('gso_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gso_last_synch',
            ])
            ->addColumn('gso_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gso_changed',
            ])
            ->addColumn('gso_created', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'after' => 'gso_changed_by',
            ])
            ->addColumn('gso_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gso_created',
            ])
            ->addIndex(['gso_ls_url'], [
                'name' => 'gso_ls_url',
                'unique' => true,
            ])
            ->addIndex(['gso_source_name'], [
                'name' => 'gso_source_name',
                'unique' => true,
            ])
            ->create();
    }
}