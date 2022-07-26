<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsRespondents extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__respondents', [
                'id' => false,
                'primary_key' => ['grs_id_user'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('grs_id_user', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
            ])
            ->addColumn('grs_ssn', 'string', [
                'null' => true,
                'limit' => 128,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grs_id_user',
            ])
            ->addColumn('grs_iso_lang', 'char', [
                'null' => false,
                'default' => 'nl',
                'limit' => 2,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grs_ssn',
            ])
            ->addColumn('grs_first_name', 'string', [
                'null' => true,
                'limit' => 30,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grs_iso_lang',
            ])
            ->addColumn('grs_surname_prefix', 'string', [
                'null' => true,
                'limit' => 10,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grs_first_name',
            ])
            ->addColumn('grs_last_name', 'string', [
                'null' => true,
                'limit' => 50,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grs_surname_prefix',
            ])
            ->addColumn('grs_gender', 'char', [
                'null' => false,
                'default' => 'U',
                'limit' => 1,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grs_last_name',
            ])
            ->addColumn('grs_birthday', 'date', [
                'null' => true,
                'after' => 'grs_gender',
            ])
            ->addColumn('grs_address_1', 'string', [
                'null' => true,
                'limit' => 80,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grs_birthday',
            ])
            ->addColumn('grs_address_2', 'string', [
                'null' => true,
                'limit' => 80,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grs_address_1',
            ])
            ->addColumn('grs_zipcode', 'string', [
                'null' => true,
                'limit' => 10,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grs_address_2',
            ])
            ->addColumn('grs_city', 'string', [
                'null' => true,
                'limit' => 40,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grs_zipcode',
            ])
            ->addColumn('grs_iso_country', 'char', [
                'null' => false,
                'default' => 'NL',
                'limit' => 2,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grs_city',
            ])
            ->addColumn('grs_phone_1', 'string', [
                'null' => true,
                'limit' => 25,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grs_iso_country',
            ])
            ->addColumn('grs_phone_2', 'string', [
                'null' => true,
                'limit' => 25,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'grs_phone_1',
            ])
            ->addColumn('grs_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'grs_phone_2',
            ])
            ->addColumn('grs_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'grs_changed',
            ])
            ->addColumn('grs_created', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => '',
                'after' => 'grs_changed_by',
            ])
            ->addColumn('grs_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'grs_created',
            ])
            ->addIndex(['grs_ssn'], [
                'name' => 'grs_ssn',
                'unique' => true,
            ])
            ->create();
    }
}