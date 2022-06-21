<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsGroups extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__groups', [
                'id' => false,
                'primary_key' => ['ggp_id_group'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('ggp_id_group', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('ggp_name', 'string', [
                'null' => false,
                'limit' => 30,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'ggp_id_group',
            ])
            ->addColumn('ggp_description', 'string', [
                'null' => false,
                'limit' => 50,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'ggp_name',
            ])
            ->addColumn('ggp_role', 'string', [
                'null' => false,
                'default' => 'respondent',
                'limit' => 150,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'ggp_description',
            ])
            ->addColumn('ggp_may_set_groups', 'string', [
                'null' => true,
                'limit' => 250,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'ggp_role',
            ])
            ->addColumn('ggp_default_group', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'ggp_may_set_groups',
            ])
            ->addColumn('ggp_group_active', 'boolean', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'ggp_default_group',
            ])
            ->addColumn('ggp_staff_members', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'ggp_group_active',
            ])
            ->addColumn('ggp_respondent_members', 'boolean', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'ggp_staff_members',
            ])
            ->addColumn('ggp_allowed_ip_ranges', 'text', [
                'null' => true,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'ggp_respondent_members',
            ])
            ->addColumn('ggp_no_2factor_ip_ranges', 'text', [
                'null' => true,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'ggp_allowed_ip_ranges',
            ])
            ->addColumn('ggp_2factor_set', 'integer', [
                'null' => false,
                'default' => '50',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'ggp_no_2factor_ip_ranges',
            ])
            ->addColumn('ggp_2factor_not_set', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'ggp_2factor_set',
            ])
            ->addColumn('ggp_respondent_browse', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'ggp_2factor_not_set',
            ])
            ->addColumn('ggp_respondent_edit', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'ggp_respondent_browse',
            ])
            ->addColumn('ggp_respondent_show', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'ggp_respondent_edit',
            ])
            ->addColumn('ggp_mask_settings', 'text', [
                'null' => true,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'ggp_respondent_show',
            ])
            ->addColumn('ggp_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'ggp_mask_settings',
            ])
            ->addColumn('ggp_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'ggp_changed',
            ])
            ->addColumn('ggp_created', 'timestamp', [
                'null' => false,
                'after' => 'ggp_changed_by',
            ])
            ->addColumn('ggp_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'ggp_created',
            ])
            ->create();
    }
}