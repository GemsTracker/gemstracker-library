<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsTokens extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__tokens', [
                'id' => false,
                'primary_key' => ['gto_id_token'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gto_id_token', 'string', [
                'null' => false,
                'limit' => 9,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ])
            ->addColumn('gto_id_respondent_track', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gto_id_token',
            ])
            ->addForeignKey('gto_id_respondent_track', 'gems__respondent2track', 'gr2t_id_respondent_track')
            ->addColumn('gto_id_round', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gto_id_respondent_track',
            ])
            ->addForeignKey('gto_id_round', 'gems__rounds', 'gro_id_round')
            ->addColumn('gto_id_respondent', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gto_id_round',
            ])
            ->addForeignKey('gto_id_respondent', 'gems__respondents', 'grs_id_user')
            ->addColumn('gto_id_organization', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gto_id_respondent',
            ])
            ->addForeignKey('gto_id_organization', 'gems__organizations', 'gor_id_organization')
            ->addColumn('gto_id_track', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gto_id_organization',
            ])
            ->addForeignKey('gto_id_track', 'gems__tracks', 'gtr_id_track')
            ->addColumn('gto_id_survey', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gto_id_track',
            ])
            ->addForeignKey('gto_id_survey', 'gems__surveys', 'gsu_id_survey')
            ->addColumn('gto_round_order', 'integer', [
                'null' => false,
                'default' => '10',
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'gto_id_survey',
            ])
            ->addColumn('gto_icon_file', 'string', [
                'null' => true,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gto_round_order',
            ])
            ->addColumn('gto_round_description', 'string', [
                'null' => true,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gto_icon_file',
            ])
            ->addColumn('gto_id_relationfield', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'after' => 'gto_round_description',
            ])
            ->addColumn('gto_id_relation', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'after' => 'gto_id_relationfield',
            ])
            ->addColumn('gto_valid_from', 'datetime', [
                'null' => true,
                'after' => 'gto_id_relation',
            ])
            ->addColumn('gto_valid_from_manual', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gto_valid_from',
            ])
            ->addColumn('gto_valid_until', 'datetime', [
                'null' => true,
                'after' => 'gto_valid_from_manual',
            ])
            ->addColumn('gto_valid_until_manual', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gto_valid_until',
            ])
            ->addColumn('gto_mail_sent_date', 'date', [
                'null' => true,
                'after' => 'gto_valid_until_manual',
            ])
            ->addColumn('gto_mail_sent_num', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'after' => 'gto_mail_sent_date',
            ])
            ->addColumn('gto_start_time', 'datetime', [
                'null' => true,
                'after' => 'gto_mail_sent_num',
            ])
            ->addColumn('gto_in_source', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gto_start_time',
            ])
            ->addColumn('gto_by', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gto_in_source',
            ])
            ->addColumn('gto_completion_time', 'datetime', [
                'null' => true,
                'after' => 'gto_by',
            ])
            ->addColumn('gto_duration_in_sec', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gto_completion_time',
            ])
            ->addColumn('gto_result', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gto_duration_in_sec',
            ])
            ->addColumn('gto_comment', 'text', [
                'null' => true,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gto_result',
            ])
            ->addColumn('gto_reception_code', 'string', [
                'null' => false,
                'default' => 'OK',
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gto_comment',
            ])
            ->addColumn('gto_return_url', 'string', [
                'null' => true,
                'limit' => 250,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gto_reception_code',
            ])
            ->addColumn('gto_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gto_return_url',
            ])
            ->addColumn('gto_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gto_changed',
            ])
            ->addColumn('gto_created', 'timestamp', [
                'null' => false,
                'after' => 'gto_changed_by',
            ])
            ->addColumn('gto_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gto_created',
            ])
            ->addIndex(['gto_by'], [
                'name' => 'gto_by',
                'unique' => false,
            ])
            ->addIndex(['gto_completion_time'], [
                'name' => 'gto_completion_time',
                'unique' => false,
            ])
            ->addIndex(['gto_created'], [
                'name' => 'gto_created',
                'unique' => false,
            ])
            ->addIndex(['gto_id_organization'], [
                'name' => 'gto_id_organization',
                'unique' => false,
            ])
            ->addIndex(['gto_id_respondent'], [
                'name' => 'gto_id_respondent',
                'unique' => false,
            ])
            ->addIndex(['gto_id_respondent_track', 'gto_round_order'], [
                'name' => 'gto_id_respondent_track',
                'unique' => false,
            ])
            ->addIndex(['gto_id_round'], [
                'name' => 'gto_id_round',
                'unique' => false,
            ])
            ->addIndex(['gto_id_survey'], [
                'name' => 'gto_id_survey',
                'unique' => false,
            ])
            ->addIndex(['gto_id_track'], [
                'name' => 'gto_id_track',
                'unique' => false,
            ])
            ->addIndex(['gto_in_source'], [
                'name' => 'gto_in_source',
                'unique' => false,
            ])
            ->addIndex(['gto_reception_code'], [
                'name' => 'gto_reception_code',
                'unique' => false,
            ])
            ->addIndex(['gto_round_order'], [
                'name' => 'gto_round_order',
                'unique' => false,
            ])
            ->addIndex(['gto_valid_from', 'gto_valid_until'], [
                'name' => 'gto_valid_from',
                'unique' => false,
            ])
            ->create();
    }
}