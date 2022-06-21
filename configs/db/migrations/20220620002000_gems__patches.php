<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsPatches extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__patches', [
                'id' => false,
                'primary_key' => ['gpa_id_patch'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gpa_id_patch', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('gpa_level', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'after' => 'gpa_id_patch',
            ])
            ->addColumn('gpa_location', 'string', [
                'null' => false,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gpa_level',
            ])
            ->addColumn('gpa_name', 'string', [
                'null' => false,
                'limit' => 30,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gpa_location',
            ])
            ->addColumn('gpa_order', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'after' => 'gpa_name',
            ])
            ->addColumn('gpa_sql', 'text', [
                'null' => false,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gpa_order',
            ])
            ->addColumn('gpa_executed', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gpa_sql',
            ])
            ->addColumn('gpa_completed', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gpa_executed',
            ])
            ->addColumn('gpa_result', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gpa_completed',
            ])
            ->addColumn('gpa_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'after' => 'gpa_result',
            ])
            ->addColumn('gpa_created', 'timestamp', [
                'null' => true,
                'after' => 'gpa_changed',
            ])
            ->addIndex(['gpa_level', 'gpa_location', 'gpa_name', 'gpa_order'], [
                'name' => 'gpa_level',
                'unique' => true,
            ])
            ->create();
    }
}