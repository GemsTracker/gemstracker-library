<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsRespondent2Track2appointment extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__respondent2track2appointment', [
                'id' => false,
                'primary_key' => ['gr2t2a_id_respondent_track', 'gr2t2a_id_app_field'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gr2t2a_id_respondent_track', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
            ])
            ->addColumn('gr2t2a_id_app_field', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gr2t2a_id_respondent_track',
            ])
            ->addColumn('gr2t2a_id_appointment', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gr2t2a_id_app_field',
            ])
            ->addColumn('gr2t2a_value_manual', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gr2t2a_id_appointment',
            ])
            ->addColumn('gr2t2a_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gr2t2a_value_manual',
            ])
            ->addColumn('gr2t2a_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gr2t2a_changed',
            ])
            ->addColumn('gr2t2a_created', 'timestamp', [
                'null' => false,
                'after' => 'gr2t2a_changed_by',
            ])
            ->addColumn('gr2t2a_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gr2t2a_created',
            ])
            ->addIndex(['gr2t2a_id_appointment'], [
                'name' => 'gr2t2a_id_appointment',
                'unique' => false,
            ])
            ->create();
    }
}