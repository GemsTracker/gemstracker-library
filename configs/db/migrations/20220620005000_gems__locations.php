<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsLocations extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__locations', [
                'id' => false,
                'primary_key' => ['glo_id_location'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('glo_id_location', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('glo_name', 'string', [
                'null' => true,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'glo_id_location',
            ])
            ->addColumn('glo_organizations', 'string', [
                'null' => true,
                'limit' => 250,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'glo_name',
            ])
            ->addColumn('glo_match_to', 'string', [
                'null' => true,
                'limit' => 250,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'glo_organizations',
            ])
            ->addColumn('glo_code', 'string', [
                'null' => true,
                'limit' => 40,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'glo_match_to',
            ])
            ->addColumn('glo_url', 'string', [
                'null' => true,
                'limit' => 250,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'glo_code',
            ])
            ->addColumn('glo_url_route', 'string', [
                'null' => true,
                'limit' => 250,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'glo_url',
            ])
            ->addColumn('glo_address_1', 'string', [
                'null' => true,
                'limit' => 80,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'glo_url_route',
            ])
            ->addColumn('glo_address_2', 'string', [
                'null' => true,
                'limit' => 80,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'glo_address_1',
            ])
            ->addColumn('glo_zipcode', 'string', [
                'null' => true,
                'limit' => 10,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'glo_address_2',
            ])
            ->addColumn('glo_city', 'string', [
                'null' => true,
                'limit' => 40,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'glo_zipcode',
            ])
            ->addColumn('glo_iso_country', 'char', [
                'null' => false,
                'default' => 'NL',
                'limit' => 2,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'glo_city',
            ])
            ->addColumn('glo_phone_1', 'string', [
                'null' => true,
                'limit' => 25,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'glo_iso_country',
            ])
            ->addColumn('glo_active', 'boolean', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'glo_phone_1',
            ])
            ->addColumn('glo_filter', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'glo_active',
            ])
            ->addColumn('glo_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'glo_filter',
            ])
            ->addColumn('glo_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'glo_changed',
            ])
            ->addColumn('glo_created', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'after' => 'glo_changed_by',
            ])
            ->addColumn('glo_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'glo_created',
            ])
            ->addIndex(['glo_match_to'], [
                'name' => 'glo_match_to',
                'unique' => false,
            ])
            ->addIndex(['glo_name'], [
                'name' => 'glo_name',
                'unique' => false,
            ])
            ->create();
    }
}