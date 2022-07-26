<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsUserLogins extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__user_logins', [
                'id' => false,
                'primary_key' => ['gul_id_user'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gul_id_user', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('gul_login', 'string', [
                'null' => false,
                'limit' => 30,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gul_id_user',
            ])
            ->addColumn('gul_id_organization', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'after' => 'gul_login',
            ])
            ->addColumn('gul_user_class', 'string', [
                'null' => false,
                'default' => 'NoLogin',
                'limit' => 30,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gul_id_organization',
            ])
            ->addColumn('gul_can_login', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gul_user_class',
            ])
            ->addColumn('gul_two_factor_key', 'string', [
                'null' => true,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gul_can_login',
            ])
            ->addColumn('gul_enable_2factor', 'boolean', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gul_two_factor_key',
            ])
            ->addColumn('gul_otp_count', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gul_enable_2factor',
            ])
            ->addColumn('gul_otp_requested', 'timestamp', [
                'null' => true,
                'after' => 'gul_otp_count',
            ])
            ->addColumn('gul_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gul_otp_requested',
            ])
            ->addColumn('gul_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gul_changed',
            ])
            ->addColumn('gul_created', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => '',
                'after' => 'gul_changed_by',
            ])
            ->addColumn('gul_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gul_created',
            ])
            ->addIndex(['gul_login', 'gul_id_organization'], [
                'name' => 'gul_login',
                'unique' => true,
            ])
            ->create();
    }
}