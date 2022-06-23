<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsRespondent2Track2Field extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__respondent2track2field', [
                'id' => false,
                'primary_key' => ['gr2t2f_id_respondent_track', 'gr2t2f_id_field'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gr2t2f_id_respondent_track', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
            ])
            ->addColumn('gr2t2f_id_field', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gr2t2f_id_respondent_track',
            ])
            ->addColumn('gr2t2f_value', 'text', [
                'null' => true,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gr2t2f_id_field',
            ])
            ->addColumn('gr2t2f_value_manual', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gr2t2f_value',
            ])
            ->addColumn('gr2t2f_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gr2t2f_value_manual',
            ])
            ->addColumn('gr2t2f_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gr2t2f_changed',
            ])
            ->addColumn('gr2t2f_created', 'timestamp', [
                'null' => false,
                'after' => 'gr2t2f_changed_by',
            ])
            ->addColumn('gr2t2f_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gr2t2f_created',
            ])
            ->create();
    }
}