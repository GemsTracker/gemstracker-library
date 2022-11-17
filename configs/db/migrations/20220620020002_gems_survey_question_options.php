<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsSurveyQuestionOptions extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__survey_question_options', [
                'id' => false,
                'primary_key' => ['gsqo_id_survey', 'gsqo_name', 'gsqo_order'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gsqo_id_survey', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
            ])
            ->addForeignKey('gsqo_id_survey', 'gems__surveys', 'gsu_id_survey')
            ->addColumn('gsqo_name', 'string', [
                'null' => false,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsqo_id_survey',
            ])
            ->addColumn('gsqo_order', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
                'after' => 'gsqo_name',
            ])
            ->addColumn('gsqo_key', 'string', [
                'null' => true,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsqo_order',
            ])
            ->addColumn('gsqo_label', 'string', [
                'null' => true,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gsqo_key',
            ])
            ->addColumn('gsqo_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gsqo_label',
            ])
            ->addColumn('gsqo_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gsqo_changed',
            ])
            ->addColumn('gsqo_created', 'timestamp', [
                'null' => false,
                'after' => 'gsqo_changed_by',
            ])
            ->addColumn('gsqo_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gsqo_created',
            ])
            ->create();
    }
}