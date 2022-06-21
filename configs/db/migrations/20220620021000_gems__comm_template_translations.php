<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsCommTemplateTranslations extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__comm_template_translations', [
                'id' => false,
                'primary_key' => ['gctt_id_template', 'gctt_lang'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gctt_id_template', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
            ])
            ->addColumn('gctt_lang', 'string', [
                'null' => false,
                'limit' => 2,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gctt_id_template',
            ])
            ->addColumn('gctt_subject', 'string', [
                'null' => true,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gctt_lang',
            ])
            ->addColumn('gctt_body', 'text', [
                'null' => true,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gctt_subject',
            ])
            ->create();
    }
}