<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsAgendaStaff extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__agenda_staff', [
                'id' => false,
                'primary_key' => ['gas_id_staff'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gas_id_staff', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('gas_name', 'string', [
                'null' => true,
                'limit' => 250,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gas_id_staff',
            ])
            ->addColumn('gas_function', 'string', [
                'null' => true,
                'limit' => 50,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gas_name',
            ])
            ->addColumn('gas_id_organization', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gas_function',
            ])
            ->addColumn('gas_id_user', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gas_id_organization',
            ])
            ->addColumn('gas_match_to', 'string', [
                'null' => true,
                'limit' => 250,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gas_id_user',
            ])
            ->addColumn('gas_source', 'string', [
                'null' => false,
                'default' => 'manual',
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gas_match_to',
            ])
            ->addColumn('gas_id_in_source', 'string', [
                'null' => true,
                'limit' => 40,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gas_source',
            ])
            ->addColumn('gas_active', 'boolean', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gas_id_in_source',
            ])
            ->addColumn('gas_filter', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gas_active',
            ])
            ->addColumn('gas_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gas_filter',
            ])
            ->addColumn('gas_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gas_changed',
            ])
            ->addColumn('gas_created', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'after' => 'gas_changed_by',
            ])
            ->addColumn('gas_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gas_created',
            ])
            ->addIndex(['gas_name'], [
                'name' => 'gas_name',
                'unique' => false,
            ])
            ->create();
    }
}