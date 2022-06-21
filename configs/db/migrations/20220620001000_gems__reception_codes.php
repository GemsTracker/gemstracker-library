<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsReceptionCodes extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__reception_codes', [
                'id' => false,
                'primary_key' => ['grc_id_reception_code'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('grc_id_reception_code', 'string', [
                'null' => false,
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
            ])
            ->addColumn('grc_description', 'string', [
                'null' => false,
                'limit' => 40,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grc_id_reception_code',
            ])
            ->addColumn('grc_success', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'grc_description',
            ])
            ->addColumn('grc_for_surveys', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'grc_success',
            ])
            ->addColumn('grc_redo_survey', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'grc_for_surveys',
            ])
            ->addColumn('grc_for_tracks', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'grc_redo_survey',
            ])
            ->addColumn('grc_for_respondents', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'grc_for_tracks',
            ])
            ->addColumn('grc_overwrite_answers', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'grc_for_respondents',
            ])
            ->addColumn('grc_active', 'boolean', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'grc_overwrite_answers',
            ])
            ->addColumn('grc_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'grc_active',
            ])
            ->addColumn('grc_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'grc_changed',
            ])
            ->addColumn('grc_created', 'timestamp', [
                'null' => false,
                'after' => 'grc_changed_by',
            ])
            ->addColumn('grc_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'grc_created',
            ])
            ->addIndex(['grc_success'], [
                'name' => 'grc_success',
                'unique' => false,
            ])
            ->create();
    }
}