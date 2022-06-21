<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsMailServers extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__mail_servers', [
                'id' => false,
                'primary_key' => ['gms_from'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gms_from', 'string', [
                'null' => false,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ])
            ->addColumn('gms_server', 'string', [
                'null' => false,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gms_from',
            ])
            ->addColumn('gms_port', 'integer', [
                'null' => false,
                'default' => '25',
                'limit' => MysqlAdapter::INT_SMALL,
                'after' => 'gms_server',
            ])
            ->addColumn('gms_ssl', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gms_port',
            ])
            ->addColumn('gms_user', 'string', [
                'null' => true,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gms_ssl',
            ])
            ->addColumn('gms_password', 'string', [
                'null' => true,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gms_user',
            ])
            ->addColumn('gms_encryption', 'string', [
                'null' => true,
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gms_password',
            ])
            ->addColumn('gms_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gms_encryption',
            ])
            ->addColumn('gms_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gms_changed',
            ])
            ->addColumn('gms_created', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'after' => 'gms_changed_by',
            ])
            ->addColumn('gms_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gms_created',
            ])
            ->create();
    }
}