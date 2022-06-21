<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsEpisodeOfCare extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__episodes_of_care', [
                'id' => false,
                'primary_key' => ['gec_episode_of_care_id'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gec_episode_of_care_id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('gec_id_user', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gec_episode_of_care_id',
            ])
            ->addColumn('gec_id_organization', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gec_id_user',
            ])
            ->addColumn('gec_source', 'string', [
                'null' => false,
                'default' => 'manual',
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gec_id_organization',
            ])
            ->addColumn('gec_id_in_source', 'string', [
                'null' => true,
                'limit' => 40,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gec_source',
            ])
            ->addColumn('gec_manual_edit', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gec_id_in_source',
            ])
            ->addColumn('gec_status', 'string', [
                'null' => false,
                'default' => 'A',
                'limit' => 1,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gec_manual_edit',
            ])
            ->addColumn('gec_startdate', 'date', [
                'null' => false,
                'after' => 'gec_status',
            ])
            ->addColumn('gec_enddate', 'date', [
                'null' => true,
                'after' => 'gec_startdate',
            ])
            ->addColumn('gec_id_attended_by', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gec_enddate',
            ])
            ->addColumn('gec_subject', 'string', [
                'null' => true,
                'limit' => 250,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gec_id_attended_by',
            ])
            ->addColumn('gec_comment', 'text', [
                'null' => true,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gec_subject',
            ])
            ->addColumn('gec_diagnosis', 'string', [
                'null' => true,
                'limit' => 250,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gec_comment',
            ])
            ->addColumn('gec_diagnosis_data', 'text', [
                'null' => true,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gec_diagnosis',
            ])
            ->addColumn('gec_extra_data', 'text', [
                'null' => true,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gec_diagnosis_data',
            ])
            ->addColumn('gec_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gec_extra_data',
            ])
            ->addColumn('gec_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gec_changed',
            ])
            ->addColumn('gec_created', 'timestamp', [
                'null' => false,
                'after' => 'gec_changed_by',
            ])
            ->addColumn('gec_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gec_created',
            ])
            ->create();
    }
}