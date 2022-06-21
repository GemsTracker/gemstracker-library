<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsUserIds extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__user_ids', [
                'id' => false,
                'primary_key' => ['gui_id_user'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gui_id_user', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
            ])
            ->addColumn('gui_created', 'timestamp', [
                'null' => false,
                'after' => 'gui_id_user',
            ])
            ->create();
    }
}