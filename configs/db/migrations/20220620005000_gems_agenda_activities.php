<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsAgendaActivities extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__agenda_activities', [
                'id' => false,
                'primary_key' => ['gaa_id_activity'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gaa_id_activity', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('gaa_name', 'string', [
                'null' => true,
                'limit' => 250,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gaa_id_activity',
            ])
            ->addColumn('gaa_id_organization', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gaa_name',
            ])
            ->addForeignKey('gaa_id_organization', 'gems__organizations', 'gor_id_organization')
            ->addColumn('gaa_name_for_resp', 'string', [
                'null' => true,
                'limit' => 50,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gaa_id_organization',
            ])
            ->addColumn('gaa_match_to', 'string', [
                'null' => true,
                'limit' => 250,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gaa_name_for_resp',
            ])
            ->addColumn('gaa_code', 'string', [
                'null' => true,
                'limit' => 40,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gaa_match_to',
            ])
            ->addColumn('gaa_active', 'boolean', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gaa_code',
            ])
            ->addColumn('gaa_filter', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gaa_active',
            ])
            ->addColumn('gaa_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gaa_filter',
            ])
            ->addColumn('gaa_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gaa_changed',
            ])
            ->addColumn('gaa_created', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'after' => 'gaa_changed_by',
            ])
            ->addColumn('gaa_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gaa_created',
            ])
            ->addIndex(['gaa_name'], [
                'name' => 'gaa_name',
                'unique' => false,
            ])
            ->create();
    }
}