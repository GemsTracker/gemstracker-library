<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsCommMessengers extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__comm_messengers', [
                'id' => false,
                'primary_key' => ['gcm_id_messenger'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gcm_id_messenger', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('gcm_id_order', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'after' => 'gcm_id_messenger',
            ])
            ->addColumn('gcm_type', 'string', [
                'null' => false,
                'limit' => 32,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gcm_id_order',
            ])
            ->addColumn('gcm_name', 'string', [
                'null' => false,
                'limit' => 32,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gcm_type',
            ])
            ->addColumn('gcm_description', 'string', [
                'null' => true,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gcm_name',
            ])
            ->addColumn('gcm_messenger_identifier', 'string', [
                'null' => true,
                'limit' => 32,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gcm_description',
            ])
            ->addColumn('gcm_active', 'integer', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gcm_messenger_identifier',
            ])
            ->addColumn('gcm_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gcm_active',
            ])
            ->addColumn('gcm_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gcm_changed',
            ])
            ->addColumn('gcm_created', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'after' => 'gcm_changed_by',
            ])
            ->addColumn('gcm_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gcm_created',
            ])
            ->addIndex(['gcm_name'], [
                'name' => 'gcm_name',
                'unique' => false,
            ])
            ->create();
    }
}