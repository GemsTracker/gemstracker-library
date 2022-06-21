<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsUserPasswords extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__user_passwords', [
                'id' => false,
                'primary_key' => ['gup_id_user'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gup_id_user', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
            ])
            ->addColumn('gup_password', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gup_id_user',
            ])
            ->addColumn('gup_reset_key', 'char', [
                'null' => true,
                'limit' => 64,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gup_password',
            ])
            ->addColumn('gup_reset_requested', 'timestamp', [
                'null' => true,
                'after' => 'gup_reset_key',
            ])
            ->addColumn('gup_reset_required', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gup_reset_requested',
            ])
            ->addColumn('gup_last_pwd_change', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'after' => 'gup_reset_required',
            ])
            ->addColumn('gup_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gup_last_pwd_change',
            ])
            ->addColumn('gup_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gup_changed',
            ])
            ->addColumn('gup_created', 'timestamp', [
                'null' => false,
                'after' => 'gup_changed_by',
            ])
            ->addColumn('gup_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gup_created',
            ])
            ->addIndex(['gup_reset_key'], [
                'name' => 'gup_reset_key',
                'unique' => true,
            ])
            ->create();
    }
}