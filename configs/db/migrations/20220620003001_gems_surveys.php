<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsSurveys extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__surveys', [
                'id' => false,
                'primary_key' => ['gsu_id_survey'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gsu_id_survey', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('gsu_survey_name', 'string', [
                'null' => false,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsu_id_survey',
            ])
            ->addColumn('gsu_survey_description', 'string', [
                'null' => true,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsu_survey_name',
            ])
            ->addColumn('gsu_external_description', 'string', [
                'null' => true,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsu_survey_description',
            ])
            ->addColumn('gsu_survey_languages', 'string', [
                'null' => true,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsu_external_description',
            ])
            ->addColumn('gsu_surveyor_id', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'gsu_survey_languages',
            ])
            ->addColumn('gsu_surveyor_active', 'boolean', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gsu_surveyor_id',
            ])
            ->addColumn('gsu_survey_pdf', 'string', [
                'null' => true,
                'limit' => 128,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsu_surveyor_active',
            ])
            ->addColumn('gsu_beforeanswering_event', 'string', [
                'null' => true,
                'limit' => 128,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsu_survey_pdf',
            ])
            ->addColumn('gsu_completed_event', 'string', [
                'null' => true,
                'limit' => 128,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsu_beforeanswering_event',
            ])
            ->addColumn('gsu_display_event', 'string', [
                'null' => true,
                'limit' => 128,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsu_completed_event',
            ])
            ->addColumn('gsu_id_source', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'after' => 'gsu_display_event',
            ])
            ->addColumn('gsu_active', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gsu_id_source',
            ])
            ->addColumn('gsu_status', 'string', [
                'null' => true,
                'limit' => 127,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsu_active',
            ])
            ->addColumn('gsu_survey_warnings', 'string', [
                'null' => true,
                'limit' => 250,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsu_status',
            ])
            ->addColumn('gsu_id_primary_group', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gsu_survey_warnings',
            ])
            ->addColumn('gsu_answers_by_group', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gsu_id_primary_group',
            ])
            ->addColumn('gsu_answer_groups', 'string', [
                'null' => true,
                'limit' => 250,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsu_answers_by_group',
            ])
            ->addColumn('gsu_allow_export', 'boolean', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gsu_answer_groups',
            ])
            ->addColumn('gsu_mail_code', 'integer', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gsu_allow_export',
            ])
            ->addColumn('gsu_insertable', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gsu_mail_code',
            ])
            ->addColumn('gsu_valid_for_unit', 'char', [
                'null' => false,
                'default' => 'M',
                'limit' => 1,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsu_insertable',
            ])
            ->addColumn('gsu_valid_for_length', 'integer', [
                'null' => false,
                'default' => '6',
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'gsu_valid_for_unit',
            ])
            ->addColumn('gsu_insert_organizations', 'string', [
                'null' => true,
                'limit' => 250,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsu_valid_for_length',
            ])
            ->addColumn('gsu_result_field', 'string', [
                'null' => true,
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsu_insert_organizations',
            ])
            ->addColumn('gsu_agenda_result', 'string', [
                'null' => true,
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsu_result_field',
            ])
            ->addColumn('gsu_duration', 'string', [
                'null' => true,
                'limit' => 50,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsu_agenda_result',
            ])
            ->addColumn('gsu_code', 'string', [
                'null' => true,
                'limit' => 64,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsu_duration',
            ])
            ->addColumn('gsu_export_code', 'string', [
                'null' => true,
                'limit' => 64,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsu_code',
            ])
            ->addColumn('gsu_hash', 'char', [
                'null' => true,
                'limit' => 32,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsu_export_code',
            ])
            ->addColumn('gsu_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gsu_hash',
            ])
            ->addColumn('gsu_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gsu_changed',
            ])
            ->addColumn('gsu_created', 'timestamp', [
                'null' => false,
                'after' => 'gsu_changed_by',
            ])
            ->addColumn('gsu_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gsu_created',
            ])
            ->addIndex(['gsu_active'], [
                'name' => 'gsu_active',
                'unique' => false,
            ])
            ->addIndex(['gsu_code'], [
                'name' => 'gsu_code',
                'unique' => false,
            ])
            ->addIndex(['gsu_id_primary_group'], [
                'name' => 'gsu_id_primary_group',
                'unique' => false,
            ])
            ->addIndex(['gsu_surveyor_active'], [
                'name' => 'gsu_surveyor_active',
                'unique' => false,
            ])
            ->create();
    }
}