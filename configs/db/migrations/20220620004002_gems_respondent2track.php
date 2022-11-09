<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsRespondent2Track extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__respondent2track', [
                'id' => false,
                'primary_key' => ['gr2t_id_respondent_track'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gr2t_id_respondent_track', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('gr2t_id_user', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gr2t_id_respondent_track',
            ])
            ->addForeignKey('gr2t_id_user', 'gems__respondents', 'grs_id_user')
            ->addColumn('gr2t_id_track', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gr2t_id_user',
            ])
            ->addForeignKey('gr2t_id_track', 'gems__tracks', 'gtr_id_track')
            ->addColumn('gr2t_track_info', 'string', [
                'null' => true,
                'limit' => 250,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gr2t_id_track',
            ])
            ->addColumn('gr2t_start_date', 'datetime', [
                'null' => true,
                'after' => 'gr2t_track_info',
            ])
            ->addColumn('gr2t_end_date', 'datetime', [
                'null' => true,
                'after' => 'gr2t_start_date',
            ])
            ->addColumn('gr2t_end_date_manual', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gr2t_end_date',
            ])
            ->addColumn('gr2t_id_organization', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gr2t_end_date_manual',
            ])
            ->addForeignKey('gr2t_id_organization', 'gems__organizations', 'gor_id_organization')
            ->addColumn('gr2t_mailable', 'integer', [
                'null' => false,
                'default' => '100',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gr2t_id_organization',
            ])
            ->addForeignKey('gr2t_mailable', 'gems__mail_codes', 'gmc_id')
            ->addColumn('gr2t_active', 'boolean', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gr2t_mailable',
            ])
            ->addColumn('gr2t_count', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'after' => 'gr2t_active',
            ])
            ->addColumn('gr2t_completed', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'after' => 'gr2t_count',
            ])
            ->addColumn('gr2t_reception_code', 'string', [
                'null' => false,
                'default' => 'OK',
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gr2t_completed',
            ])
            ->addColumn('gr2t_comment', 'string', [
                'null' => true,
                'limit' => 250,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gr2t_reception_code',
            ])
            ->addColumn('gr2t_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gr2t_comment',
            ])
            ->addColumn('gr2t_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gr2t_changed',
            ])
            ->addColumn('gr2t_created', 'timestamp', [
                'null' => false,
                'after' => 'gr2t_changed_by',
            ])
            ->addColumn('gr2t_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gr2t_created',
            ])
            ->addIndex(['gr2t_created_by'], [
                'name' => 'gr2t_created_by',
                'unique' => false,
            ])
            ->addIndex(['gr2t_id_organization'], [
                'name' => 'gr2t_id_organization',
                'unique' => false,
            ])
            ->addIndex(['gr2t_id_track'], [
                'name' => 'gr2t_id_track',
                'unique' => false,
            ])
            ->addIndex(['gr2t_id_user'], [
                'name' => 'gr2t_id_user',
                'unique' => false,
            ])
            ->addIndex(['gr2t_start_date'], [
                'name' => 'gr2t_start_date',
                'unique' => false,
            ])
            ->create();
    }
}