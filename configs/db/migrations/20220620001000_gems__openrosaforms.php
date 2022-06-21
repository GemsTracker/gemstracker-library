<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsOpenRosaForms extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__openrosaforms', [
                'id' => false,
                'primary_key' => ['gof_id'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gof_id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'identity' => 'enable',
            ])
            ->addColumn('gof_form_id', 'string', [
                'null' => false,
                'limit' => 249,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gof_id',
            ])
            ->addColumn('gof_form_version', 'string', [
                'null' => false,
                'limit' => 249,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gof_form_id',
            ])
            ->addColumn('gof_form_active', 'integer', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'gof_form_version',
            ])
            ->addColumn('gof_form_title', 'text', [
                'null' => false,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gof_form_active',
            ])
            ->addColumn('gof_form_xml', 'string', [
                'null' => false,
                'limit' => 64,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gof_form_title',
            ])
            ->addColumn('gof_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gof_form_xml',
            ])
            ->addColumn('gof_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'after' => 'gof_changed',
            ])
            ->addColumn('gof_created', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'after' => 'gof_changed_by',
            ])
            ->addColumn('gof_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'after' => 'gof_created',
            ])
            ->create();
    }
}