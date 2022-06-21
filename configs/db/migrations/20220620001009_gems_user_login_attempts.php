<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsUserLoginAttempts extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__user_login_attempts', [
                'id' => false,
                'primary_key' => ['gula_login', 'gula_id_organization'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gula_login', 'string', [
                'null' => false,
                'limit' => 30,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ])
            ->addColumn('gula_id_organization', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'after' => 'gula_login',
            ])
            ->addColumn('gula_failed_logins', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'after' => 'gula_id_organization',
            ])
            ->addColumn('gula_last_failed', 'timestamp', [
                'null' => true,
                'after' => 'gula_failed_logins',
            ])
            ->addColumn('gula_block_until', 'timestamp', [
                'null' => true,
                'after' => 'gula_last_failed',
            ])
            ->create();
    }
}