<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsTrackAppointments extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__track_appointments', [
                'id' => false,
                'primary_key' => ['gtap_id_app_field'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gtap_id_app_field', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('gtap_id_track', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'after' => 'gtap_id_app_field',
            ])
            ->addColumn('gtap_id_order', 'integer', [
                'null' => false,
                'default' => '10',
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'gtap_id_track',
            ])
            ->addColumn('gtap_field_name', 'string', [
                'null' => false,
                'limit' => 200,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtap_id_order',
            ])
            ->addColumn('gtap_field_code', 'string', [
                'null' => true,
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtap_field_name',
            ])
            ->addColumn('gtap_field_description', 'string', [
                'null' => true,
                'limit' => 200,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtap_field_code',
            ])
            ->addColumn('gtap_to_track_info', 'boolean', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gtap_field_description',
            ])
            ->addColumn('gtap_track_info_label', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gtap_to_track_info',
            ])
            ->addColumn('gtap_required', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gtap_track_info_label',
            ])
            ->addColumn('gtap_readonly', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gtap_required',
            ])
            ->addColumn('gtap_filter_id', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gtap_readonly',
            ])
            ->addColumn('gtap_after_next', 'boolean', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gtap_filter_id',
            ])
            ->addColumn('gtap_min_diff_length', 'integer', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'gtap_after_next',
            ])
            ->addColumn('gtap_min_diff_unit', 'char', [
                'null' => false,
                'default' => 'D',
                'limit' => 1,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtap_min_diff_length',
            ])
            ->addColumn('gtap_max_diff_exists', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gtap_min_diff_unit',
            ])
            ->addColumn('gtap_max_diff_length', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'gtap_max_diff_exists',
            ])
            ->addColumn('gtap_max_diff_unit', 'char', [
                'null' => false,
                'default' => 'D',
                'limit' => 1,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtap_max_diff_length',
            ])
            ->addColumn('gtap_uniqueness', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gtap_max_diff_unit',
            ])
            ->addColumn('gtap_create_track', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'gtap_uniqueness',
            ])
            ->addColumn('gtap_create_wait_days', 'integer', [
                'null' => false,
                'default' => '182',
                'limit' => MysqlAdapter::INT_BIG,
                'after' => 'gtap_create_track',
            ])
            ->addColumn('gtap_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gtap_create_wait_days',
            ])
            ->addColumn('gtap_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gtap_changed',
            ])
            ->addColumn('gtap_created', 'timestamp', [
                'null' => false,
                'after' => 'gtap_changed_by',
            ])
            ->addColumn('gtap_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gtap_created',
            ])
            ->addIndex(['gtap_id_order'], [
                'name' => 'gtap_id_order',
                'unique' => false,
            ])
            ->addIndex(['gtap_id_track'], [
                'name' => 'gtap_id_track',
                'unique' => false,
            ])
            ->create();
    }
}