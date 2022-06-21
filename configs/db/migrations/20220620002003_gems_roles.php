<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsRoles extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__roles', [
                'id' => false,
                'primary_key' => ['grl_id_role'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('grl_id_role', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('grl_name', 'string', [
                'null' => false,
                'limit' => 30,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grl_id_role',
            ])
            ->addColumn('grl_description', 'string', [
                'null' => false,
                'limit' => 50,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grl_name',
            ])
            ->addColumn('grl_parents', 'text', [
                'null' => true,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grl_description',
            ])
            ->addColumn('grl_privileges', 'text', [
                'null' => true,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grl_parents',
            ])
            ->addColumn('grl_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'grl_privileges',
            ])
            ->addColumn('grl_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'grl_changed',
            ])
            ->addColumn('grl_created', 'timestamp', [
                'null' => false,
                'after' => 'grl_changed_by',
            ])
            ->addColumn('grl_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'grl_created',
            ])
            ->create();
    }
}