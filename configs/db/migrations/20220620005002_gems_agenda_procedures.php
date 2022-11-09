<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsAgendaProcedures extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__agenda_procedures', [
                'id' => false,
                'primary_key' => ['gapr_id_procedure'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gapr_id_procedure', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('gapr_name', 'string', [
                'null' => true,
                'limit' => 250,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gapr_id_procedure',
            ])
            ->addColumn('gapr_id_organization', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gapr_name',
            ])
            ->addForeignKey('gapr_id_organization', 'gems__organizations', 'gor_id_organization')
            ->addColumn('gapr_name_for_resp', 'string', [
                'null' => true,
                'limit' => 50,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gapr_id_organization',
            ])
            ->addColumn('gapr_match_to', 'string', [
                'null' => true,
                'limit' => 250,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gapr_name_for_resp',
            ])
            ->addColumn('gapr_code', 'string', [
                'null' => true,
                'limit' => 40,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gapr_match_to',
            ])
            ->addColumn('gapr_active', 'boolean', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gapr_code',
            ])
            ->addColumn('gapr_filter', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gapr_active',
            ])
            ->addColumn('gapr_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gapr_filter',
            ])
            ->addColumn('gapr_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gapr_changed',
            ])
            ->addColumn('gapr_created', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'after' => 'gapr_changed_by',
            ])
            ->addColumn('gapr_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gapr_created',
            ])
            ->addIndex(['gapr_name'], [
                'name' => 'gapr_name',
                'unique' => false,
            ])
            ->create();
    }
}