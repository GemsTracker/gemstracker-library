<?php

use Phinx\Db\Adapter\MysqlAdapter;

class GemsAppointments extends Phinx\Migration\AbstractMigration
{
    public function change()
    {
        $this->table('gems__appointments', [
                'id' => false,
                'primary_key' => ['gap_id_appointment'],
                'engine' => 'InnoDB',
                'encoding' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'comment' => '',
                'row_format' => 'DYNAMIC',
            ])
            ->addColumn('gap_id_appointment', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'identity' => 'enable',
            ])
            ->addColumn('gap_id_user', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gap_id_appointment',
            ])
            ->addForeignKey('gap_id_user', 'gems__respondents', 'grs_id_user')
            ->addColumn('gap_id_organization', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gap_id_user',
            ])
            ->addForeignKey('gap_id_organization', 'gems__organizations', 'gor_id_organization')
            ->addColumn('gap_id_episode', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gap_id_organization',
            ])
            ->addForeignKey('gap_id_episode', 'gems__episodes_of_care', 'gec_episode_of_care_id')
            ->addColumn('gap_source', 'string', [
                'null' => false,
                'default' => 'manual',
                'limit' => 20,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gap_id_episode',
            ])
            ->addColumn('gap_id_in_source', 'string', [
                'null' => true,
                'limit' => 40,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gap_source',
            ])
            ->addColumn('gap_last_synch', 'timestamp', [
                'null' => true,
                'after' => 'gap_id_in_source',
            ])
            ->addColumn('gap_manual_edit', 'boolean', [
                'null' => false,
                'default' => '0',
                'limit' => MysqlAdapter::INT_TINY,
                'after' => 'gap_last_synch',
            ])
            ->addColumn('gap_code', 'string', [
                'null' => false,
                'default' => 'A',
                'limit' => 1,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gap_manual_edit',
            ])
            ->addColumn('gap_status', 'string', [
                'null' => false,
                'default' => 'AC',
                'limit' => 2,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gap_code',
            ])
            ->addColumn('gap_admission_time', 'datetime', [
                'null' => false,
                'after' => 'gap_status',
            ])
            ->addColumn('gap_discharge_time', 'datetime', [
                'null' => true,
                'after' => 'gap_admission_time',
            ])
            ->addColumn('gap_id_attended_by', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gap_discharge_time',
            ])
            ->addForeignKey('gap_id_attended_by', 'gems__agenda_staff', 'gas_id_staff')
            ->addColumn('gap_id_referred_by', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gap_id_attended_by',
            ])
            ->addForeignKey('gap_id_referred_by', 'gems__agenda_staff', 'gas_id_staff')
            ->addColumn('gap_id_activity', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gap_id_referred_by',
            ])
            ->addForeignKey('gap_id_activity', 'gems__agenda_activities', 'gaa_id_activity')
            ->addColumn('gap_id_procedure', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gap_id_activity',
            ])
            ->addForeignKey('gap_id_procedure', 'gems__agenda_procedures', 'gapr_id_procedure')
            ->addColumn('gap_id_location', 'integer', [
                'null' => true,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gap_id_procedure',
            ])
            ->addForeignKey('gap_id_location', 'gems__locations', 'glo_id_location')
            ->addColumn('gap_diagnosis_code', 'string', [
                'null' => true,
                'limit' => 50,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gap_id_location',
            ])
            ->addForeignKey('gap_diagnosis_code', 'gems__agenda_diagnoses', 'gad_diagnosis_code')
            ->addColumn('gap_subject', 'string', [
                'null' => true,
                'limit' => 250,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gap_diagnosis_code',
            ])
            ->addColumn('gap_comment', 'text', [
                'null' => true,
                'limit' => 65535,
                'collation' => 'utf8mb4_unicode_ci',
                'encoding' => 'utf8mb4',
                'after' => 'gap_subject',
            ])
            ->addColumn('gap_changed', 'timestamp', [
                'null' => false,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
                'after' => 'gap_comment',
            ])
            ->addColumn('gap_changed_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gap_changed',
            ])
            ->addColumn('gap_created', 'timestamp', [
                'null' => false,
                'after' => 'gap_changed_by',
            ])
            ->addColumn('gap_created_by', 'integer', [
                'null' => false,
                'limit' => MysqlAdapter::INT_BIG,
                'signed' => false,
                'after' => 'gap_created',
            ])
            ->addIndex(['gap_admission_time'], [
                'name' => 'gap_admission_time',
                'unique' => false,
            ])
            ->addIndex(['gap_code'], [
                'name' => 'gap_code',
                'unique' => false,
            ])
            ->addIndex(['gap_id_activity'], [
                'name' => 'gap_id_activity',
                'unique' => false,
            ])
            ->addIndex(['gap_id_attended_by'], [
                'name' => 'gap_id_attended_by',
                'unique' => false,
            ])
            ->addIndex(['gap_id_in_source', 'gap_id_organization', 'gap_source'], [
                'name' => 'gap_id_in_source',
                'unique' => true,
            ])
            ->addIndex(['gap_id_location'], [
                'name' => 'gap_id_location',
                'unique' => false,
            ])
            ->addIndex(['gap_id_procedure'], [
                'name' => 'gap_id_procedure',
                'unique' => false,
            ])
            ->addIndex(['gap_id_referred_by'], [
                'name' => 'gap_id_referred_by',
                'unique' => false,
            ])
            ->addIndex(['gap_id_user', 'gap_id_organization'], [
                'name' => 'gap_id_user',
                'unique' => false,
            ])
            ->addIndex(['gap_status'], [
                'name' => 'gap_status',
                'unique' => false,
            ])
            ->create();
    }
}