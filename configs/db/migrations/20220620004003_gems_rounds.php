<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsRounds extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__rounds', [
                'id' => false,
                'primary_key' => ['gro_id_round'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gro_id_round', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('gro_id_track', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gro_id_round',
            ])
            ->addForeignKey('gro_id_track', 'gems__tracks', 'gtr_id_track')
            ->addColumn('gro_id_order', 'integer', [
                'null' => false,
                'default' => '10',
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'gro_id_track',
            ])
            ->addColumn('gro_id_survey', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gro_id_order',
            ])
            ->addForeignKey('gro_id_survey', 'gems__surveys', 'gsu_id_survey')
            ->addColumn('gro_id_relationfield', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'after' => 'gro_id_survey',
            ])
            ->addColumn('gro_survey_name', 'string', [
                'null' => false,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gro_id_relationfield',
            ])
            ->addColumn('gro_round_description', 'string', [
                'null' => true,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gro_survey_name',
            ])
            ->addColumn('gro_icon_file', 'string', [
                'null' => true,
                'limit' => 100,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gro_round_description',
            ])
            ->addColumn('gro_changed_event', 'string', [
                'null' => true,
                'limit' => 128,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gro_icon_file',
            ])
            ->addColumn('gro_display_event', 'string', [
                'null' => true,
                'limit' => 128,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gro_changed_event',
            ])
            ->addColumn('gro_valid_after_id', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gro_display_event',
            ])
            ->addForeignKey('gro_valid_after_id', 'gems__rounds', 'gro_id_round')
            ->addColumn('gro_valid_after_source', 'string', [
                'null' => false,
                'default' => 'tok',
                'limit' => 12,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gro_valid_after_id',
            ])
            ->addColumn('gro_valid_after_field', 'string', [
                'null' => false,
                'default' => 'gto_valid_from',
                'limit' => 64,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gro_valid_after_source',
            ])
            ->addColumn('gro_valid_after_unit', 'char', [
                'null' => false,
                'default' => 'M',
                'limit' => 1,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gro_valid_after_field',
            ])
            ->addColumn('gro_valid_after_length', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'gro_valid_after_unit',
            ])
            ->addColumn('gro_valid_for_id', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gro_valid_after_length',
            ])
            ->addForeignKey('gro_valid_for_id', 'gems__rounds', 'gro_id_round')
            ->addColumn('gro_valid_for_source', 'string', [
                'null' => false,
                'default' => 'nul',
                'limit' => 12,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gro_valid_for_id',
            ])
            ->addColumn('gro_valid_for_field', 'string', [
                'null' => true,
                'limit' => 64,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gro_valid_for_source',
            ])
            ->addColumn('gro_valid_for_unit', 'char', [
                'null' => false,
                'default' => 'M',
                'limit' => 1,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gro_valid_for_field',
            ])
            ->addColumn('gro_valid_for_length', 'integer', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_REGULAR,
                'after' => 'gro_valid_for_unit',
            ])
            ->addColumn('gro_condition', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gro_valid_for_length',
            ])
            ->addForeignKey('gro_condition', 'gems__conditions', 'gcon_id')
            ->addColumn('gro_organizations', 'string', [
                'null' => true,
                'limit' => 250,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gro_condition',
            ])
            ->addColumn('gro_active', 'boolean', [
                'null' => false,
                'default' => '1',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gro_organizations',
            ])
            ->addColumn('gro_code', 'string', [
                'null' => true,
                'limit' => 64,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gro_active',
            ])
            ->addColumn('gro_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gro_code',
            ])
            ->addColumn('gro_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gro_changed',
            ])
            ->addColumn('gro_created', 'timestamp', [
                'null' => false,
                'after' => 'gro_changed_by',
            ])
            ->addColumn('gro_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gro_created',
            ])
            ->addIndex(['gro_id_order'], [
                'name' => 'gro_id_order',
                'unique' => false,
            ])
            ->addIndex(['gro_id_survey'], [
                'name' => 'gro_id_survey',
                'unique' => false,
            ])
            ->addIndex(['gro_id_track', 'gro_id_order'], [
                'name' => 'gro_id_track',
                'unique' => false,
            ])
            ->create();
    }
}