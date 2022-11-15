<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsLogRespondentConsents extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__log_respondent_consents', [
                'id' => false,
                'primary_key' => ['glrc_id'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('glrc_id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('glrc_id_user', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'glrc_id',
            ])
            ->addColumn('glrc_id_organization', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'glrc_id_user',
            ])
            ->addForeignKey('glrc_id_organization', 'gems__organizations', 'gor_id_organization')
            ->addColumn('glrc_consent_field', 'string', [
                'null' => false,
                'default' => 'gr2o_consent',
                'limit' => 30,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'glrc_id_organization',
            ])
            ->addColumn('glrc_old_consent', 'string', [
                'null' => true,
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'glrc_consent_field',
            ])
            ->addColumn('glrc_new_consent', 'string', [
                'null' => false,
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'glrc_old_consent',
            ])
            ->addColumn('glrc_created', 'timestamp', [
                'null' => false,
                'after' => 'glrc_new_consent',
            ])
            ->addColumn('glrc_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'glrc_created',
            ])
            ->create();
    }
}