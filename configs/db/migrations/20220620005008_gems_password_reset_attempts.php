<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsPasswordResetAttempts extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__password_reset_attempts', [
                'id' => false,
                'primary_key' => ['gpra_id'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gpra_id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
            ])
            ->addColumn('gpra_id_organization', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gpra_id',
            ])
            ->addForeignKey('gpra_id_organization', 'gems__organizations', 'gor_id_organization')
            ->addColumn('gpra_ip_address', 'string', [
                'null' => true,
                'limit' => 45,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gpra_id_organization',
            ])
            ->addColumn('gpra_attempt_at', 'timestamp', [
                'null' => false,
                'after' => 'gpra_ip_address',
            ])
            ->create();
    }
}