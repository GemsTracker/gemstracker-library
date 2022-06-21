<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsMailCodes extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__mail_codes', [
                'id' => false,
                'primary_key' => ['gmc_id'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gmc_id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_TINY,
            ])
            ->addColumn('gmc_mail_to_target', 'string', [
                'null' => false,
                'limit' => 40,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gmc_id',
            ])
            ->addColumn('gmc_mail_cause_target', 'string', [
                'null' => false,
                'limit' => 40,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gmc_mail_to_target',
            ])
            ->addColumn('gmc_code', 'string', [
                'null' => true,
                'limit' => 40,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gmc_mail_cause_target',
            ])
            ->addColumn('gmc_for_surveys', 'boolean', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gmc_code',
            ])
            ->addColumn('gmc_for_tracks', 'boolean', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gmc_for_surveys',
            ])
            ->addColumn('gmc_for_respondents', 'boolean', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gmc_for_tracks',
            ])
            ->addColumn('gmc_active', 'boolean', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gmc_for_respondents',
            ])
            ->addColumn('gmc_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gmc_active',
            ])
            ->addColumn('gmc_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gmc_changed',
            ])
            ->addColumn('gmc_created', 'timestamp', [
                'null' => false,
                'after' => 'gmc_changed_by',
            ])
            ->addColumn('gmc_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gmc_created',
            ])
            ->create();
    }
}