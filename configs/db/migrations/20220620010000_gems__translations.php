<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsTranslations extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__translations', [
                'id' => false,
                'primary_key' => ['gtrs_id'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gtrs_id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('gtrs_table', 'string', [
                'null' => false,
                'limit' => 128,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtrs_id',
            ])
            ->addColumn('gtrs_field', 'string', [
                'null' => false,
                'limit' => 128,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtrs_table',
            ])
            ->addColumn('gtrs_keys', 'string', [
                'null' => false,
                'limit' => 128,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtrs_field',
            ])
            ->addColumn('gtrs_iso_lang', 'string', [
                'null' => false,
                'limit' => 6,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtrs_keys',
            ])
            ->addColumn('gtrs_translation', 'text', [
                'null' => true,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gtrs_iso_lang',
            ])
            ->addColumn('gtrs_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gtrs_translation',
            ])
            ->addColumn('gtrs_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gtrs_changed',
            ])
            ->addColumn('gtrs_created', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'after' => 'gtrs_changed_by',
            ])
            ->addColumn('gtrs_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gtrs_created',
            ])
            ->addIndex(['gtrs_field'], [
                'name' => 'gtrs_field',
                'unique' => false,
            ])
            ->addIndex(['gtrs_iso_lang'], [
                'name' => 'gtrs_iso_lang',
                'unique' => false,
            ])
            ->addIndex(['gtrs_keys'], [
                'name' => 'gtrs_keys',
                'unique' => false,
            ])
            ->addIndex(['gtrs_table'], [
                'name' => 'gtrs_table',
                'unique' => false,
            ])
            ->create();
    }
}