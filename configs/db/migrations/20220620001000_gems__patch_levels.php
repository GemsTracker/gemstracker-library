<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsOrganizations extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__patch_levels', [
                'id' => false,
                'primary_key' => ['gpl_level'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gpl_level', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_REGULAR,
                'signed' => false,
            ])
            ->addColumn('gpl_created', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'after' => 'gpl_level',
            ])
            ->addIndex(['gpl_level'], [
                'name' => 'gpl_level',
                'unique' => true,
            ])
            ->create();
    }
}