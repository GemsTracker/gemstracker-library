<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsAppointmentFilters extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__appointment_filters', [
                'id' => false,
                'primary_key' => ['gaf_id'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gaf_id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('gaf_class', 'string', [
                'null' => false,
                'limit' => 200,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gaf_id',
            ])
            ->addColumn('gaf_manual_name', 'string', [
                'null' => true,
                'limit' => 200,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gaf_class',
            ])
            ->addColumn('gaf_calc_name', 'string', [
                'null' => false,
                'limit' => 200,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gaf_manual_name',
            ])
            ->addColumn('gaf_id_order', 'integer', [
                'null' => false,
                'default' => '10',
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'gaf_calc_name',
            ])
            ->addColumn('gaf_filter_text1', 'string', [
                'null' => true,
                'limit' => 200,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gaf_id_order',
            ])
            ->addColumn('gaf_filter_text2', 'string', [
                'null' => true,
                'limit' => 200,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gaf_filter_text1',
            ])
            ->addColumn('gaf_filter_text3', 'string', [
                'null' => true,
                'limit' => 200,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gaf_filter_text2',
            ])
            ->addColumn('gaf_filter_text4', 'string', [
                'null' => true,
                'limit' => 200,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gaf_filter_text3',
            ])
            ->addColumn('gaf_active', 'boolean', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gaf_filter_text4',
            ])
            ->addColumn('gaf_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gaf_active',
            ])
            ->addColumn('gaf_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gaf_changed',
            ])
            ->addColumn('gaf_created', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'after' => 'gaf_changed_by',
            ])
            ->addColumn('gaf_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gaf_created',
            ])
            ->create();
    }
}