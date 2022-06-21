<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsAgendaDiagnoses extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__agenda_diagnoses', [
                'id' => false,
                'primary_key' => ['gad_diagnosis_code'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gad_diagnosis_code', 'string', [
                'null' => false,
                'limit' => 50,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ])
            ->addColumn('gad_description', 'string', [
                'null' => true,
                'limit' => 250,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gad_diagnosis_code',
            ])
            ->addColumn('gad_coding_method', 'string', [
                'null' => false,
                'default' => 'DBC',
                'limit' => 10,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gad_description',
            ])
            ->addColumn('gad_code', 'string', [
                'null' => true,
                'limit' => 40,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gad_coding_method',
            ])
            ->addColumn('gad_source', 'string', [
                'null' => false,
                'default' => 'manual',
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gad_code',
            ])
            ->addColumn('gad_id_in_source', 'string', [
                'null' => true,
                'limit' => 40,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gad_source',
            ])
            ->addColumn('gad_active', 'boolean', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gad_id_in_source',
            ])
            ->addColumn('gad_filter', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gad_active',
            ])
            ->addColumn('gad_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gad_filter',
            ])
            ->addColumn('gad_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gad_changed',
            ])
            ->addColumn('gad_created', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'after' => 'gad_changed_by',
            ])
            ->addColumn('gad_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gad_created',
            ])
            ->addIndex(['gad_description'], [
                'name' => 'gad_description',
                'unique' => false,
            ])
            ->create();
    }
}