<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsStaff extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__staff', [
                'id' => false,
                'primary_key' => ['gsf_id_user'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gsf_id_user', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
            ])
            ->addColumn('gsf_login', 'string', [
                'null' => false,
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsf_id_user',
            ])
            ->addColumn('gsf_id_organization', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'after' => 'gsf_login',
            ])
            ->addColumn('gsf_active', 'boolean', [
                'null' => true,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gsf_id_organization',
            ])
            ->addColumn('gsf_id_primary_group', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gsf_active',
            ])
            ->addColumn('gsf_iso_lang', 'char', [
                'null' => false,
                'default' => 'en',
                'limit' => 2,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsf_id_primary_group',
            ])
            ->addColumn('gsf_logout_on_survey', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gsf_iso_lang',
            ])
            ->addColumn('gsf_mail_watcher', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gsf_logout_on_survey',
            ])
            ->addColumn('gsf_email', 'string', [
                'null' => true,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsf_mail_watcher',
            ])
            ->addColumn('gsf_first_name', 'string', [
                'null' => true,
                'limit' => 30,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsf_email',
            ])
            ->addColumn('gsf_surname_prefix', 'string', [
                'null' => true,
                'limit' => 10,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsf_first_name',
            ])
            ->addColumn('gsf_last_name', 'string', [
                'null' => true,
                'limit' => 30,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsf_surname_prefix',
            ])
            ->addColumn('gsf_gender', 'char', [
                'null' => false,
                'default' => 'U',
                'limit' => 1,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsf_last_name',
            ])
            ->addColumn('gsf_job_title', 'string', [
                'null' => true,
                'limit' => 64,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsf_gender',
            ])
            ->addColumn('gsf_phone_1', 'string', [
                'null' => true,
                'limit' => 25,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsf_job_title',
            ])
            ->addColumn('gsf_is_embedded', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gsf_phone_1',
            ])
            ->addColumn('gsf_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gsf_is_embedded',
            ])
            ->addColumn('gsf_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gsf_changed',
            ])
            ->addColumn('gsf_created', 'timestamp', [
                'null' => false,
                'after' => 'gsf_changed_by',
            ])
            ->addColumn('gsf_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gsf_created',
            ])
            ->addIndex(['gsf_email'], [
                'name' => 'gsf_email',
                'unique' => false,
            ])
            ->addIndex(['gsf_login', 'gsf_id_organization'], [
                'name' => 'gsf_login',
                'unique' => true,
            ])
            ->create();
    }
}