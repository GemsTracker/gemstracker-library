<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsRespondent2org extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__respondent2org', [
                'id' => false,
                'primary_key' => ['gr2o_patient_nr', 'gr2o_id_organization'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gr2o_patient_nr', 'string', [
                'null' => false,
                'limit' => 15,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ])
            ->addColumn('gr2o_id_organization', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gr2o_patient_nr',
            ])
            ->addColumn('gr2o_id_user', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gr2o_id_organization',
            ])
            ->addColumn('gr2o_email', 'string', [
                'null' => true,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gr2o_id_user',
            ])
            ->addColumn('gr2o_mailable', 'integer', [
                'null' => false,
                'default' => '100',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gr2o_email',
            ])
            ->addColumn('gr2o_comments', 'text', [
                'null' => true,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gr2o_mailable',
            ])
            ->addColumn('gr2o_consent', 'string', [
                'null' => false,
                'default' => 'Unknown',
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gr2o_comments',
            ])
            ->addColumn('gr2o_reception_code', 'string', [
                'null' => false,
                'default' => 'OK',
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gr2o_consent',
            ])
            ->addColumn('gr2o_opened', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gr2o_reception_code',
            ])
            ->addColumn('gr2o_opened_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gr2o_opened',
            ])
            ->addColumn('gr2o_changed', 'timestamp', [
                'null' => false,
                'after' => 'gr2o_opened_by',
            ])
            ->addColumn('gr2o_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gr2o_changed',
            ])
            ->addColumn('gr2o_created', 'timestamp', [
                'null' => false,
                'after' => 'gr2o_changed_by',
            ])
            ->addColumn('gr2o_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gr2o_created',
            ])
            ->addIndex(['gr2o_changed_by'], [
                'name' => 'gr2o_changed_by',
                'unique' => false,
            ])
            ->addIndex(['gr2o_consent'], [
                'name' => 'gr2o_consent',
                'unique' => false,
            ])
            ->addIndex(['gr2o_email'], [
                'name' => 'gr2o_email',
                'unique' => false,
            ])
            ->addIndex(['gr2o_id_organization'], [
                'name' => 'gr2o_id_organization',
                'unique' => false,
            ])
            ->addIndex(['gr2o_id_user', 'gr2o_id_organization'], [
                'name' => 'gr2o_id_user',
                'unique' => true,
            ])
            ->addIndex(['gr2o_opened'], [
                'name' => 'gr2o_opened',
                'unique' => false,
            ])
            ->addIndex(['gr2o_opened_by'], [
                'name' => 'gr2o_opened_by',
                'unique' => false,
            ])
            ->addIndex(['gr2o_reception_code'], [
                'name' => 'gr2o_reception_code',
                'unique' => false,
            ])
            ->create();
    }
}