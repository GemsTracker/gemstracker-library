<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsCommJobs extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__comm_jobs', [
                'id' => false,
                'primary_key' => ['gcj_id_job'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gcj_id_job', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('gcj_id_order', 'integer', [
                'null' => false,
                'default' => '10',
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'gcj_id_job',
            ])
            ->addColumn('gcj_id_communication_messenger', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gcj_id_order',
            ])
            ->addForeignKey('gcj_id_communication_messenger', 'gems__comm_messengers', 'gcm_id_messenger')
            ->addColumn('gcj_id_message', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gcj_id_communication_messenger',
            ])
            ->addForeignKey('gcj_id_message', 'gems__comm_templates', 'gct_id_template')
            ->addColumn('gcj_id_user_as', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gcj_id_message',
            ])
            ->addForeignKey('gcj_id_user_as', 'gems__staff', 'gsf_id_user')
            ->addColumn('gcj_active', 'boolean', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gcj_id_user_as',
            ])
            ->addColumn('gcj_from_method', 'string', [
                'null' => false,
                'limit' => 1,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gcj_active',
            ])
            ->addColumn('gcj_from_fixed', 'string', [
                'null' => true,
                'limit' => 254,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gcj_from_method',
            ])
            ->addColumn('gcj_to_method', 'string', [
                'null' => true,
                'default' => 'R',
                'limit' => 1,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gcj_from_fixed',
            ])
            ->addColumn('gcj_fallback_method', 'string', [
                'null' => true,
                'default' => 'O',
                'limit' => 1,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gcj_to_method',
            ])
            ->addColumn('gcj_fallback_fixed', 'string', [
                'null' => true,
                'limit' => 254,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gcj_fallback_method',
            ])
            ->addColumn('gcj_process_method', 'string', [
                'null' => false,
                'limit' => 1,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gcj_fallback_fixed',
            ])
            ->addColumn('gcj_filter_mode', 'string', [
                'null' => false,
                'limit' => 1,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gcj_process_method',
            ])
            ->addColumn('gcj_filter_days_between', 'integer', [
                'null' => false,
                'default' => '7',
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'after' => 'gcj_filter_mode',
            ])
            ->addColumn('gcj_filter_max_reminders', 'integer', [
                'null' => false,
                'default' => '3',
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'after' => 'gcj_filter_days_between',
            ])
            ->addColumn('gcj_target', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gcj_filter_max_reminders',
            ])
            ->addColumn('gcj_target_group', 'string', [
                'null' => true,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gcj_target',
            ])
            ->addColumn('gcj_id_organization', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gcj_target_group',
            ])
            ->addForeignKey('gcj_id_organization', 'gems__organizations', 'gor_id_organization')
            ->addColumn('gcj_id_track', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gcj_id_organization',
            ])
            ->addForeignKey('gcj_id_track', 'gems__tracks', 'gtr_id_track')
            ->addColumn('gcj_round_description', 'string', [
                'null' => true,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gcj_id_track',
            ])
            ->addColumn('gcj_id_survey', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gcj_round_description',
            ])
            ->addForeignKey('gcj_id_survey', 'gems__surveys', 'gsu_id_survey')
            ->addColumn('gcj_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gcj_id_survey',
            ])
            ->addColumn('gcj_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gcj_changed',
            ])
            ->addColumn('gcj_created', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'after' => 'gcj_changed_by',
            ])
            ->addColumn('gcj_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gcj_created',
            ])
            ->create();
    }
}