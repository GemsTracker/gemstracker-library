<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsLogRespondent2Track2Field extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__log_respondent2track2field', [
                'id' => false,
                'primary_key' => ['glrtf_id'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('glrtf_id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('glrtf_id_respondent_track', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'glrtf_id',
            ])
            ->addColumn('glrtf_id_sub', 'string', [
                'null' => true,
                'limit' => 8,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'glrtf_id_respondent_track',
            ])
            ->addColumn('glrtf_id_field', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'after' => 'glrtf_id_sub',
            ])
            ->addColumn('glrtf_old_value', 'text', [
                'null' => true,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'glrtf_id_field',
            ])
            ->addColumn('glrtf_old_value_manual', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'glrtf_old_value',
            ])
            ->addColumn('glrtf_new_value', 'text', [
                'null' => true,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'glrtf_old_value_manual',
            ])
            ->addColumn('glrtf_new_value_manual', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'glrtf_new_value',
            ])
            ->addColumn('glrtf_created', 'timestamp', [
                'null' => false,
                'after' => 'glrtf_new_value_manual',
            ])
            ->addColumn('glrtf_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'glrtf_created',
            ])
            ->create();
    }
}