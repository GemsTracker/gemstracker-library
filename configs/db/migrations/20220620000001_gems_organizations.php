<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsOrganizations extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__organizations', [
                'id' => false,
                'primary_key' => ['gor_id_organization'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gor_id_organization', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('gor_name', 'string', [
                'null' => false,
                'limit' => 50,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gor_id_organization',
            ])
            ->addColumn('gor_code', 'string', [
                'null' => true,
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gor_name',
            ])
            ->addColumn('gor_user_class', 'string', [
                'null' => false,
                'default' => 'StaffUser',
                'limit' => 30,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gor_code',
            ])
            ->addColumn('gor_location', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gor_user_class',
            ])
            ->addColumn('gor_url', 'string', [
                'null' => true,
                'limit' => 127,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gor_location',
            ])
            ->addColumn('gor_url_base', 'string', [
                'null' => true,
                'limit' => 1270,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gor_url',
            ])
            ->addColumn('gor_task', 'string', [
                'null' => true,
                'limit' => 50,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gor_url_base',
            ])
            ->addColumn('gor_provider_id', 'string', [
                'null' => true,
                'limit' => 10,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gor_task',
            ])
            ->addColumn('gor_accessible_by', 'text', [
                'null' => true,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gor_provider_id',
            ])
            ->addColumn('gor_contact_name', 'string', [
                'null' => true,
                'limit' => 50,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gor_accessible_by',
            ])
            ->addColumn('gor_contact_email', 'string', [
                'null' => true,
                'limit' => 127,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gor_contact_name',
            ])
            ->addColumn('gor_contact_sms_from', 'string', [
                'null' => true,
                'limit' => 12,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gor_contact_email',
            ])
            ->addColumn('gor_mail_watcher', 'boolean', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gor_contact_sms_from',
            ])
            ->addColumn('gor_welcome', 'text', [
                'null' => true,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gor_mail_watcher',
            ])
            ->addColumn('gor_signature', 'text', [
                'null' => true,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gor_welcome',
            ])
            ->addColumn('gor_respondent_edit', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gor_signature',
            ])
            ->addColumn('gor_respondent_show', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gor_respondent_edit',
            ])
            ->addColumn('gor_respondent_subscribe', 'string', [
                'null' => true,
                'default' => '',
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gor_respondent_show',
            ])
            ->addColumn('gor_respondent_unsubscribe', 'string', [
                'null' => true,
                'default' => '',
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gor_respondent_subscribe',
            ])
            ->addColumn('gor_token_ask', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gor_respondent_unsubscribe',
            ])
            ->addColumn('gor_style', 'string', [
                'null' => false,
                'default' => 'gems',
                'limit' => 15,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gor_token_ask',
            ])
            ->addColumn('gor_resp_change_event', 'string', [
                'null' => true,
                'limit' => 128,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gor_style',
            ])
            ->addColumn('gor_iso_lang', 'char', [
                'null' => false,
                'default' => 'en',
                'limit' => 2,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gor_resp_change_event',
            ])
            ->addColumn('gor_has_login', 'boolean', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gor_iso_lang',
            ])
            ->addColumn('gor_has_respondents', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gor_has_login',
            ])
            ->addColumn('gor_add_respondents', 'boolean', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gor_has_respondents',
            ])
            ->addColumn('gor_respondent_group', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gor_add_respondents',
            ])
            ->addColumn('gor_create_account_template', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gor_respondent_group',
            ])
            ->addColumn('gor_reset_pass_template', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gor_create_account_template',
            ])
            ->addColumn('gor_allowed_ip_ranges', 'text', [
                'null' => true,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gor_reset_pass_template',
            ])
            ->addColumn('gor_active', 'boolean', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gor_allowed_ip_ranges',
            ])
            ->addColumn('gor_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gor_active',
            ])
            ->addColumn('gor_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gor_changed',
            ])
            ->addColumn('gor_created', 'timestamp', [
                'null' => false,
                'after' => 'gor_changed_by',
            ])
            ->addColumn('gor_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gor_created',
            ])
            ->addIndex(['gor_code'], [
                'name' => 'gor_code',
                'unique' => false,
            ])
            ->create();
    }
}