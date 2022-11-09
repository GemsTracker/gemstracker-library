<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsLogActivity extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__log_activity', [
                'id' => false,
                'primary_key' => ['gla_id'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gla_id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('gla_action', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'after' => 'gla_id',
            ])
            ->addForeignKey('gla_action', 'gems__log_setup', 'gls_id_action')
            ->addColumn('gla_respondent_id', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gla_action',
            ])
            ->addForeignKey('gla_respondent_id', 'gems__respondents', 'grs_id_user')
            ->addColumn('gla_by', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gla_respondent_id',
            ])
            ->addForeignKey('gla_by', 'gems__staff', 'gsf_id_user')
            ->addColumn('gla_organization', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gla_by',
            ])
            ->addForeignKey('gla_organization', 'gems__organizations', 'gor_id_organization')
            ->addColumn('gla_role', 'string', [
                'null' => false,
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gla_organization',
            ])
            ->addColumn('gla_changed', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gla_role',
            ])
            ->addColumn('gla_message', 'text', [
                'null' => true,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gla_changed',
            ])
            ->addColumn('gla_data', 'text', [
                'null' => true,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gla_message',
            ])
            ->addColumn('gla_method', 'string', [
                'null' => false,
                'limit' => 10,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gla_data',
            ])
            ->addColumn('gla_remote_ip', 'string', [
                'null' => false,
                'limit' => 64,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gla_method',
            ])
            ->addColumn('gla_created', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'after' => 'gla_remote_ip',
            ])
            ->addIndex(['gla_action'], [
                'name' => 'gla_action',
                'unique' => false,
            ])
            ->addIndex(['gla_by'], [
                'name' => 'gla_by',
                'unique' => false,
            ])
            ->addIndex(['gla_created'], [
                'name' => 'gla_created',
                'unique' => false,
            ])
            ->addIndex(['gla_organization'], [
                'name' => 'gla_organization',
                'unique' => false,
            ])
            ->addIndex(['gla_respondent_id'], [
                'name' => 'gla_respondent_id',
                'unique' => false,
            ])
            ->addIndex(['gla_role'], [
                'name' => 'gla_role',
                'unique' => false,
            ])
            ->create();
    }
}