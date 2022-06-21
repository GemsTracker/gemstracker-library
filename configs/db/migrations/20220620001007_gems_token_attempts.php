<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsTokenAttempts extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__token_attempts', [
                'id' => false,
                'primary_key' => ['gta_id_attempt'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gta_id_attempt', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('gta_id_token', 'string', [
                'null' => false,
                'limit' => 9,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gta_id_attempt',
            ])
            ->addColumn('gta_ip_address', 'string', [
                'null' => false,
                'limit' => 64,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gta_id_token',
            ])
            ->addColumn('gta_datetime', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'after' => 'gta_ip_address',
            ])
            ->addColumn('gta_activated', 'boolean', [
                'null' => true,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gta_datetime',
            ])
            ->create();
    }
}