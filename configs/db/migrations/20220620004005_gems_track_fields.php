<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsTrackFields extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__track_fields', [
                'id' => false,
                'primary_key' => ['gtf_id_field'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gtf_id_field', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('gtf_id_track', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'after' => 'gtf_id_field',
            ])
            ->addColumn('gtf_id_order', 'integer', [
                'null' => false,
                'default' => '10',
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'gtf_id_track',
            ])
            ->addColumn('gtf_field_name', 'string', [
                'null' => false,
                'limit' => 200,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtf_id_order',
            ])
            ->addColumn('gtf_field_code', 'string', [
                'null' => true,
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtf_field_name',
            ])
            ->addColumn('gtf_field_description', 'string', [
                'null' => true,
                'limit' => 200,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtf_field_code',
            ])
            ->addColumn('gtf_field_values', 'text', [
                'null' => true,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtf_field_description',
            ])
            ->addColumn('gtf_field_default', 'string', [
                'null' => true,
                'limit' => 50,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtf_field_values',
            ])
            ->addColumn('gtf_calculate_using', 'string', [
                'null' => true,
                'limit' => 50,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtf_field_default',
            ])
            ->addColumn('gtf_field_type', 'string', [
                'null' => false,
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtf_calculate_using',
            ])
            ->addColumn('gtf_to_track_info', 'boolean', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gtf_field_type',
            ])
            ->addColumn('gtf_track_info_label', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gtf_to_track_info',
            ])
            ->addColumn('gtf_required', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gtf_track_info_label',
            ])
            ->addColumn('gtf_readonly', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gtf_required',
            ])
            ->addColumn('gtf_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gtf_readonly',
            ])
            ->addColumn('gtf_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gtf_changed',
            ])
            ->addColumn('gtf_created', 'timestamp', [
                'null' => false,
                'after' => 'gtf_changed_by',
            ])
            ->addColumn('gtf_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gtf_created',
            ])
            ->addIndex(['gtf_id_order'], [
                'name' => 'gtf_id_order',
                'unique' => false,
            ])
            ->addIndex(['gtf_id_track'], [
                'name' => 'gtf_id_track',
                'unique' => false,
            ])
            ->create();
    }
}