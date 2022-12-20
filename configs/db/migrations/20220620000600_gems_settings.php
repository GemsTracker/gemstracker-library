<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsSettings extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__settings', [
                'id' => false,
                'primary_key' => ['gst_key'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gst_key', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('gst_value', 'text', [
                'null' => true,
            ])
            ->addColumn('gst_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gst_value',
            ])
            ->addColumn('gst_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gst_changed',
            ])
            ->addColumn('gst_created', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => '',
                'after' => 'gst_changed_by',
            ])
            ->addColumn('gst_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gst_created',
            ])
            ->create();
    }
}