<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsLogRespondentCommunications extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__log_respondent_communications', [
                'id' => false,
                'primary_key' => ['grco_id_action'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('grco_id_action', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('grco_id_to', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'grco_id_action',
            ])
            ->addColumn('grco_id_by', 'integer', [
                'null' => true,
                'default' => '0',
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'grco_id_to',
            ])
            ->addColumn('grco_organization', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'grco_id_by',
            ])
            ->addColumn('grco_id_token', 'string', [
                'null' => true,
                'limit' => 9,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grco_organization',
            ])
            ->addColumn('grco_method', 'string', [
                'null' => false,
                'limit' => 12,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grco_id_token',
            ])
            ->addColumn('grco_topic', 'string', [
                'null' => false,
                'limit' => 120,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grco_method',
            ])
            ->addColumn('grco_address', 'string', [
                'null' => true,
                'limit' => 120,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grco_topic',
            ])
            ->addColumn('grco_sender', 'string', [
                'null' => true,
                'limit' => 120,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grco_address',
            ])
            ->addColumn('grco_comments', 'string', [
                'null' => true,
                'limit' => 120,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grco_sender',
            ])
            ->addColumn('grco_id_message', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'grco_comments',
            ])
            ->addColumn('grco_id_job', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'grco_id_message',
            ])
            ->addColumn('grco_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'after' => 'grco_id_job',
            ])
            ->addColumn('grco_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'grco_changed',
            ])
            ->addColumn('grco_created', 'timestamp', [
                'null' => false,
                'after' => 'grco_changed_by',
            ])
            ->addColumn('grco_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'grco_created',
            ])
            ->create();
    }
}