<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsSurveyQuestions extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__survey_questions', [
                'id' => false,
                'primary_key' => ['gsq_id_survey', 'gsq_name'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gsq_id_survey', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
            ])
            ->addForeignKey('gsq_id_survey', 'gems__surveys', 'gsu_id_survey')
            ->addColumn('gsq_name', 'string', [
                'null' => false,
                'limit' => 100,
                'collation' => 'utf8mb4_bin',
                'encoding' => 'utf8mb4',
                'after' => 'gsq_id_survey',
            ])
            ->addColumn('gsq_name_parent', 'string', [
                'null' => true,
                'limit' => 100,
                'collation' => 'utf8mb4_bin',
                'encoding' => 'utf8mb4',
                'after' => 'gsq_name',
            ])
            ->addColumn('gsq_order', 'integer', [
                'null' => false,
                'default' => '10',
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'after' => 'gsq_name_parent',
            ])
            ->addColumn('gsq_type', 'integer', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_SMALL,
                'after' => 'gsq_order',
            ])
            ->addColumn('gsq_class', 'string', [
                'null' => true,
                'limit' => 50,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsq_type',
            ])
            ->addColumn('gsq_group', 'string', [
                'null' => true,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsq_class',
            ])
            ->addColumn('gsq_label', 'text', [
                'null' => true,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsq_group',
            ])
            ->addColumn('gsq_description', 'text', [
                'null' => true,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsq_label',
            ])
            ->addColumn('gsq_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gsq_description',
            ])
            ->addColumn('gsq_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gsq_changed',
            ])
            ->addColumn('gsq_created', 'timestamp', [
                'null' => false,
                'after' => 'gsq_changed_by',
            ])
            ->addColumn('gsq_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gsq_created',
            ])
            ->create();
    }
}