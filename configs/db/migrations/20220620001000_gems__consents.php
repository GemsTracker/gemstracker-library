<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsConsents extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__consents', [
                'id' => false,
                'primary_key' => ['gco_description'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gco_description', 'string', [
                'null' => false,
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ])
            ->addColumn('gco_order', 'integer', [
                'null' => false,
                'default' => '10',
                'limit' => MysqlAdapter::INT_SMALL,
                'after' => 'gco_description',
            ])
            ->addColumn('gco_code', 'string', [
                'null' => false,
                'default' => 'do not use',
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gco_order',
            ])
            ->addColumn('gco_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gco_code',
            ])
            ->addColumn('gco_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gco_changed',
            ])
            ->addColumn('gco_created', 'timestamp', [
                'null' => false,
                'after' => 'gco_changed_by',
            ])
            ->addColumn('gco_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gco_created',
            ])
            ->create();
    }
}