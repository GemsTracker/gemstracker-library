<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsSubscriptionAttempts extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__subscription_attempts', [
                'id' => false,
                'primary_key' => ['gsa_id_attempt'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gsa_id_attempt', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('gsa_type_attempt', 'string', [
                'null' => false,
                'limit' => 16,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsa_id_attempt',
            ])
            ->addColumn('gsa_ip_address', 'string', [
                'null' => false,
                'limit' => 64,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsa_type_attempt',
            ])
            ->addColumn('gsa_datetime', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'after' => 'gsa_ip_address',
            ])
            ->addColumn('gsa_activated', 'boolean', [
                'null' => true,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gsa_datetime',
            ])
            ->create();
    }
}