<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsLogSetup extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__log_setup', [
                'id' => false,
                'primary_key' => ['gls_id_action'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gls_id_action', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('gls_name', 'string', [
                'null' => false,
                'limit' => 64,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gls_id_action',
            ])
            ->addColumn('gls_when_no_user', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gls_name',
            ])
            ->addColumn('gls_on_action', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gls_when_no_user',
            ])
            ->addColumn('gls_on_post', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gls_on_action',
            ])  
            ->addColumn('gls_on_change', 'boolean', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gls_on_post',
            ])
            ->addColumn('gls_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gls_on_change',
            ])
            ->addColumn('gls_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gls_changed',
            ])
            ->addColumn('gls_created', 'timestamp', [
                'null' => false,
                'after' => 'gls_changed_by',
            ])
            ->addColumn('gls_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gls_created',
            ])
            ->addIndex(['gls_name'], [
                'name' => 'gls_name',
                'unique' => true,
            ])
            ->addIndex(['gls_name'], [
                'name' => 'gls_name_2',
                'unique' => false,
            ])
            ->create(); 
    }
}