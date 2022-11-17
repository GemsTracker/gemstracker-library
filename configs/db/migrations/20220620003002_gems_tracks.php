<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsTracks extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__tracks', [
                'id' => false,
                'primary_key' => ['gtr_id_track'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gtr_id_track', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('gtr_track_name', 'string', [
                'null' => false,
                'limit' => 40,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtr_id_track',
            ])
            ->addColumn('gtr_external_description', 'string', [
                'null' => true,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtr_track_name',
            ])
            ->addColumn('gtr_track_info', 'string', [
                'null' => true,
                'limit' => 250,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtr_external_description',
            ])
            ->addColumn('gtr_code', 'string', [
                'null' => true,
                'limit' => 64,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtr_track_info',
            ])
            ->addColumn('gtr_date_start', 'date', [
                'null' => false,
                'after' => 'gtr_code',
            ])
            ->addColumn('gtr_date_until', 'date', [
                'null' => true,
                'after' => 'gtr_date_start',
            ])
            ->addColumn('gtr_active', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gtr_date_until',
            ])
            ->addColumn('gtr_survey_rounds', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'after' => 'gtr_active',
            ])
            ->addColumn('gtr_track_class', 'string', [
                'null' => false,
                'limit' => 64,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtr_survey_rounds',
            ])
            ->addColumn('gtr_beforefieldupdate_event', 'string', [
                'null' => true,
                'limit' => 128,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtr_track_class',
            ])
            ->addColumn('gtr_calculation_event', 'string', [
                'null' => true,
                'limit' => 128,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtr_beforefieldupdate_event',
            ])
            ->addColumn('gtr_completed_event', 'string', [
                'null' => true,
                'limit' => 128,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtr_calculation_event',
            ])
            ->addColumn('gtr_fieldupdate_event', 'string', [
                'null' => true,
                'limit' => 128,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtr_completed_event',
            ])
            ->addColumn('gtr_organizations', 'string', [
                'null' => true,
                'limit' => 250,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtr_fieldupdate_event',
            ])
            ->addColumn('gtr_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gtr_organizations',
            ])
            ->addColumn('gtr_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gtr_changed',
            ])
            ->addColumn('gtr_created', 'timestamp', [
                'null' => false,
                'after' => 'gtr_changed_by',
            ])
            ->addColumn('gtr_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gtr_created',
            ])
            ->addIndex(['gtr_active'], [
                'name' => 'gtr_active',
                'unique' => false,
            ])
            ->addIndex(['gtr_track_class'], [
                'name' => 'gtr_track_class',
                'unique' => false,
            ])
            ->addIndex(['gtr_track_name'], [
                'name' => 'gtr_track_name',
                'unique' => true,
            ])
            ->addIndex(['gtr_track_name'], [
                'name' => 'gtr_track_name_2',
                'unique' => false,
            ])
            ->create();
    }
}