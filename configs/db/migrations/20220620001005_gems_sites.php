<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsSites extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__sites', [
                'id' => false,
                'primary_key' => ['gsi_id'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gsi_id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('gsi_url', 'string', [
                'null' => false,
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsi_id',
            ])
            ->addColumn('gsi_order', 'integer', [
                'null' => false,
                'default' => '100',
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'gsi_url',
            ])
            ->addColumn('gsi_select_organizations', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gsi_order',
            ])
            ->addColumn('gsi_organizations', 'string', [
                'null' => false,
                'default' => '||',
                'limit' => 255,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsi_select_organizations',
            ])
            ->addColumn('gsi_style', 'string', [
                'null' => false,
                'default' => 'gems',
                'limit' => 15,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsi_organizations',
            ])
            ->addColumn('gsi_style_fixed', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gsi_style',
            ])
            ->addColumn('gsi_iso_lang', 'char', [
                'null' => false,
                'default' => 'en',
                'limit' => 2,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsi_style_fixed',
            ])
            ->addColumn('gsi_active', 'boolean', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gsi_iso_lang',
            ])
            ->addColumn('gsi_blocked', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gsi_active',
            ])
            ->addColumn('gsi_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gsi_blocked',
            ])
            ->addColumn('gsi_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gsi_changed',
            ])
            ->addColumn('gsi_created', 'timestamp', [
                'null' => false,
                'after' => 'gsi_changed_by',
            ])
            ->addColumn('gsi_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gsi_created',
            ])
            ->addIndex(['gsi_order'], [
                'name' => 'gsi_order',
                'unique' => false,
            ])
            ->addIndex(['gsi_url'], [
                'name' => 'gsi_url',
                'unique' => true,
            ])
            ->create();
    }
}