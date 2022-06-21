<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsChartConfig extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__chart_config', [
                'id' => false,
                'primary_key' => ['gcc_id'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gcc_id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'identity' => 'enable',
            ])
            ->addColumn('gcc_tid', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'after' => 'gcc_id',
            ])
            ->addColumn('gcc_rid', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'after' => 'gcc_tid',
            ])
            ->addColumn('gcc_sid', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'after' => 'gcc_rid',
            ])
            ->addColumn('gcc_code', 'string', [
                'null' => true,
                'limit' => 16,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gcc_sid',
            ])
            ->addColumn('gcc_config', 'text', [
                'null' => true,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gcc_code',
            ])
            ->addColumn('gcc_description', 'string', [
                'null' => true,
                'limit' => 64,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gcc_config',
            ])
            ->addColumn('gcc_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gcc_description',
            ])
            ->addColumn('gcc_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gcc_changed',
            ])
            ->addColumn('gcc_created', 'timestamp', [
                'null' => false,
                'after' => 'gcc_changed_by',
            ])
            ->addColumn('gcc_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gcc_created',
            ])
            ->addIndex(['gcc_code'], [
                'name' => 'gcc_code',
                'unique' => false,
            ])
            ->addIndex(['gcc_rid'], [
                'name' => 'gcc_rid',
                'unique' => false,
            ])
            ->addIndex(['gcc_sid'], [
                'name' => 'gcc_sid',
                'unique' => false,
            ])
            ->addIndex(['gcc_tid'], [
                'name' => 'gcc_tid',
                'unique' => false,
            ])
            ->create();
    }
}