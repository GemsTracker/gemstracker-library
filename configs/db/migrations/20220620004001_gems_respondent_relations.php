<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsRespondentRelations extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__respondent_relations', [
                'id' => false,
                'primary_key' => ['grr_id'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('grr_id', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'identity' => 'enable',
            ])
            ->addColumn('grr_id_respondent', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'after' => 'grr_id',
                'signed' => false,
            ])
            ->addForeignKey('grr_id_respondent', 'gems__respondents', 'grs_id_user')
            ->addColumn('grr_type', 'string', [
                'null' => true,
                'limit' => 64,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grr_id_respondent',
            ])
            ->addColumn('grr_id_staff', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'after' => 'grr_type',
                'signed' => false,
            ])
            ->addForeignKey('grr_id_staff', 'gems__staff', 'gsf_id_user')
            ->addColumn('grr_email', 'string', [
                'null' => true,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grr_id_staff',
            ])
            ->addColumn('grr_phone', 'string', [
                'null' => true,
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grr_email',
            ])
            ->addColumn('grr_mailable', 'integer', [
                'null' => false,
                'default' => '100',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'grr_phone',
            ])
            ->addColumn('grr_first_name', 'string', [
                'null' => true,
                'limit' => 30,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grr_mailable',
            ])
            ->addColumn('grr_last_name', 'string', [
                'null' => true,
                'limit' => 50,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grr_first_name',
            ])
            ->addColumn('grr_gender', 'char', [
                'null' => false,
                'default' => 'U',
                'limit' => 1,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grr_last_name',
            ])
            ->addColumn('grr_birthdate', 'date', [
                'null' => true,
                'after' => 'grr_gender',
            ])
            ->addColumn('grr_comments', 'text', [
                'null' => true,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grr_birthdate',
            ])
            ->addColumn('grr_active', 'boolean', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'grr_comments',
            ])
            ->addColumn('grr_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'grr_active',
            ])
            ->addColumn('grr_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'grr_changed',
            ])
            ->addColumn('grr_created', 'timestamp', [
                'null' => false,
                'after' => 'grr_changed_by',
            ])
            ->addColumn('grr_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'grr_created',
            ])
            ->addIndex(['grr_id_respondent', 'grr_id_staff'], [
                'name' => 'grr_id_respondent',
                'unique' => false,
            ])
            ->create();
    }
}